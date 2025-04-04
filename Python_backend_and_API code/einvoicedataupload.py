
import base64

import tarfile
import os
import json
import requests

from datetime import datetime

def convert_to_mysql_date(date_string):
    """
    Converts a date string in the format 'DD-MM-YYYY' or 'DD/MM/YYYY' to MySQL date format ('YYYY-MM-DD').

    Args:
        date_string: The date string to be converted.

    Returns:
        The date string in MySQL format ('YYYY-MM-DD') or None if the conversion fails.
    """

    try:
        # Attempt to parse the date string with '-' as separator
        date_obj = datetime.strptime(date_string, '%d-%m-%Y')

    except ValueError:
        try:
            # Attempt to parse the date string with '/' as separator
            date_obj = datetime.strptime(date_string, '%d/%m/%Y')

        except ValueError:
            return None  # Invalid date format

    return date_obj.strftime('%Y-%m-%d')


def extract_json_from_tar_gz(tar_gz_file):
    """
    Extracts a JSON file from a tar.gz archive, even if it's nested within a folder.

    Args:
        tar_gz_file: Path to the tar.gz file.

    Returns:
        A dictionary containing the JSON data, or None if no JSON file is found.
    """

    with tarfile.open(tar_gz_file, 'r:gz') as tar:
        for member in tar.getmembers():
            if member.isfile() and member.name.endswith(".json"):
                # Extract the member (file or folder)
                tar.extract(member)

                # Get the absolute path of the extracted file
                json_file_path = os.path.join(os.getcwd(), member.name)

                try:
                    with open(json_file_path, 'r') as f: 
                        json_data = json.load(f)
                        return json_data
                except FileNotFoundError:
                    print(f"Error: Could not open JSON file: {json_file_path}")
                    continue

    print("No JSON file found in the archive.")
    return None

def base64url_decode(encoded_string):
    """
    Decodes a Base64Url encoded string.

    Args:
        encoded_string: The Base64Url encoded string.

    Returns:
        The decoded string.
    """

    # Replace Base64Url characters with standard Base64 characters
    base64_string = encoded_string.replace('-', '+').replace('_', '/')
    
    # Pad the string with '=' to make its length a multiple of 4
    padded_string = base64_string + '=' * (-len(base64_string) % 4)

    # Decode the padded string using base64.b64decode()
    decoded_bytes = base64.b64decode(padded_string)

    return decoded_bytes.decode('utf-8')



# Example usage:
tar_gz_file = "33FULPS4391L1ZU_102024_Received_1.tar.gz" 
abid = 1000000000 # get this from cookie
# postingdate= "31-12-2024"
json_data = extract_json_from_tar_gz(tar_gz_file)

noofitems = len(json_data)
print(str(noofitems)+" docs")



def process_json_data(json_data):
    """
    Processes the given JSON data, extracting and decoding the 'SignedInvoice' field.

    Args:
        json_data: A list of dictionaries, where each dictionary contains the 'SignedInvoice' field.

    Returns:
        A list of decoded and cleaned data strings.
    """

    decoded_data_list = []
    
    for item in json_data:
        try:
            encoded_string = item["SignedInvoice"].split(".")[1]
            decoded_string = base64url_decode(encoded_string)  # Decode to UTF-8
            cleandata = json.loads(decoded_string)["data"].replace("\\", "")
            # cleandata = decoded_string.replace('{"data":"', '').replace('","iss":"NIC"}', '').replace('{"iss":"NIC","data":"', '').replace('}"}', '').replace("\\", "")
            
            #for now adding raw json here to save einvoice. 
            # Later avoid this duplication because now we are sending both decoded as well as raw json. 
            # This unnecesarily increases payload.
            cleandata = json.loads(cleandata)
            cleandata["SignedInvoice"] = item["SignedInvoice"]
            cleandata["SignedQRCode"] = item["SignedQRCode"]
            cleandata["Status"] = item["Status"]
            # print(cleandata)
            
            decoded_data_list.append(json.dumps(cleandata))
            

        except (KeyError, IndexError, ValueError) as e:
            print(f"Error processing item: {e}{item}")
            continue  # Skip to the next item if an error occurs

    return decoded_data_list


decoded_data_list = process_json_data(json_data)
# print(decoded_data_list[13])




def create_vendors(decoded_data_list_json):
    """
    Iterates through the decoded data list, extracts vendor data for each item,
    creates a vendor dictionary, and sends a POST request to the API endpoint
    for each vendor.

    Args:
        decoded_data_list_json: A JSON-decoded dictionary containing vendor data.

    Returns:
        None
    """

    # Prepare vendor data from the JSON input
    vendor_data = {
        "lglNm": decoded_data_list_json["SellerDtls"]["LglNm"],
        "addr1": decoded_data_list_json["SellerDtls"].get("Addr1", None),
        "addr2": decoded_data_list_json["SellerDtls"].get("Addr2", None),
        "addr3": decoded_data_list_json["SellerDtls"].get("Addr3", None),
        "loc": decoded_data_list_json["SellerDtls"].get("Loc", None),
        "pin": decoded_data_list_json["SellerDtls"].get("Pin", None),
        "stcd": decoded_data_list_json["SellerDtls"].get("Stcd", None),
        "gstin":decoded_data_list_json["SellerDtls"].get("Gstin", None),
        "defaulttradegl":defaulttradegl,
        "defaultbalancegl":defaultbalancegl
    }

    # Query parameters
    
    gstin = decoded_data_list_json["SellerDtls"].get("Gstin", None)

    try:
        # Send the POST request
        response = requests.post(
            f"http://127.0.0.1:8000/vendorlist?abid={abid}",
            json=vendor_data
        )

        if response.status_code == 200:
            if response.json()["status_code"]== 200:
                print("Vendor created successfully: "+str(response.json()['new_entry']["vendorid"])+"   "+str(response.json()['new_entry']["lglNm"]))
                vendordata = response.json()['new_entry']
                return vendordata

            elif response.json()["status_code"]== 403:
                print("Vendor already exist: "+str(response.json()['existing_entry']["vendorid"])+"   "+str(response.json()['existing_entry']["lglNm"]))
                vendordata = response.json()['existing_entry']  # use vendor data for recording transaction
                return vendordata

        else:
            print(f"Unexpected error: {response.status_code}, {response.json()}")

    except requests.exceptions.RequestException as e:
        print(f"Error communicating with the API: {e}")


# # Create vendors from the list
# create_vendors(decoded_data_list)




# for item in decoded_data_list:
#     try:
#         decoded_data_list_json = json.loads(item)

#         vendordata = create_vendors(decoded_data_list_json)

#         # if vendordata:
#         #     print(vendordata)  # Use vendordata outside the function
       
#     except (IndexError, KeyError) as e:
#         print(f"Error processing item: {e}")





# IMPORTANT:
# for now loop to check each line is within create_vendor function. 
# We should create one more function to create bill/record bill(after checking for duplicates and version).
# Then we should create a loop outside for each item in decoded data list we should check:
# INVOICE endpoint: whether this invoice invoice is already booked or not (using post endpoint)
# VENDOR endpoint : if not booked then check whether this vendor already exist or not and if not then it will create a new vendor and outputs vendor code( post vendor end point)  
# VENDOR endpoint : post vendor should also fetch the existing GL codes to be used for this invoice.
# INVOICE endpoint: create/record bills as per details fetched using post bill endpoint


def create_journal_einv(decoded_data_list_json):
    """
    Iterates through the decoded data list, extracts transaction data from each line,
    checks existing record, creates a JLI template, and sends a POST request to the API endpoint
    for each invoice.

    Args:
        decoded_data_list_json: A JSON-decoded dictionary containing invoice data.

    Returns:
        None
    """
    # API endpoint
    url = "http://localhost:8000/create_journal_einv"  # Replace with your actual server URL

    lines = []

    AssVal = decoded_data_list_json.get("ValDtls", {}).get("AssVal",0)
    RndOffAmt = decoded_data_list_json.get("ValDtls", {}).get("RndOffAmt",0)
    TotInvVal = decoded_data_list_json.get("ValDtls", {}).get("TotInvVal",0)
    balanceamount = decoded_data_list_json.get("ValDtls", {}).get("TotInvVal",0)

    cgstamount = 0
    sgstamount = 0
    igstamount = 0
    cessamount = 0
    OthChrg= 0

    for item in decoded_data_list_json['ItemList']:
        OthChrg = item.get("OthChrg",0)
        #for journal_line

        cgstamount += item.get("CgstAmt",0)
        sgstamount += item.get("SgstAmt",0)
        igstamount += item.get("IgstAmt",0)
        cessamount += item.get("CesAmt",0)

    tradeamount = (AssVal+RndOffAmt)
    # print(tradeamount)

    if decoded_data_list_json["DocDtls"]["Typ"] == "INV" or decoded_data_list_json["DocDtls"]["Typ"] == "DBN":
        #for journal_header
        AssVal = AssVal
        RndOffAmt = RndOffAmt
        OthChrg = OthChrg
        TotInvVal = TotInvVal
        #for journal_line
        tradeamount = (AssVal+RndOffAmt)
        cgstamount = cgstamount
        sgstamount = sgstamount
        igstamount = igstamount
        cessamount = cessamount
        balanceamount = -balanceamount

    elif decoded_data_list_json["DocDtls"]["Typ"] == "CRN":
        #for journal_header
        AssVal = -AssVal
        RndOffAmt = -RndOffAmt
        OthChrg = -OthChrg
        TotInvVal = -TotInvVal
        
        #for journal_line
        tradeamount = -(AssVal+RndOffAmt)
        cgstamount = -cgstamount
        sgstamount = -sgstamount
        igstamount = -igstamount
        cessamount = -cessamount
        balanceamount = balanceamount

        
    if decoded_data_list_json["TranDtls"].get("RegRev")=="Y":

        balanceamount=balanceamount+cgstamount+sgstamount+igstamount+cessamount
        if cgstamount is not None and cgstamount != 0:
            cgstrcmjournal = [{
                "gl_id": cgstrcmpayable,
                "amount": -cgstamount,
                "description": "GST payable on inward supplies subject to RCM"
            },
            {
                "gl_id": sgstrcmpayable,
                "amount": -sgstamount,
                "description": "GST payable on inward supplies subject to RCM"
            }]

            lines.extend(cgstrcmjournal)
        elif igstamount is not None and igstamount != 0:
            igstrcmjournal = [{
                "gl_id": igstrcmpayable,
                "amount": -igstamount,
                "description": "GST payable on inward supplies subject to RCM"
            }]
            lines.extend(igstrcmjournal)
        if cessamount is not None and cessamount != 0:
            cessrcmjournal = [{
                "gl_id": cessrcmpayable,
                "amount": -cessamount,
                "description": "GST payable on inward supplies subject to RCM"
            }]
            lines.extend(cessrcmjournal)

    # GST entry creation - later add input eligibility check
    if decoded_data_list_json.get("ValDtls", {}).get("CgstVal") is not None and decoded_data_list_json["ValDtls"]["CgstVal"] != 0:
        cgstjournal = [{
            "gl_id": cgstinputgl,
            "amount": cgstamount,
            "description": "purchase of materials......."
        },
        {
            "gl_id": sgstinputgl,
            "amount": sgstamount,
            "description": "purchase of materials......."
        }]

        lines.extend(cgstjournal)
    elif decoded_data_list_json.get("ValDtls", {}).get("IgstVal") is not None and decoded_data_list_json["ValDtls"]["IgstVal"] != 0:
        igstjournal = [{
            "gl_id": igstinputgl,
            "amount": igstamount,
            "description": "purchase of materials......."
        }]
        lines.extend(igstjournal)

    if vendordata["defaulttradegl"] == None:
        tradejournal = [{
            "gl_id": defaulttradegl,
            "amount": tradeamount,
            "description": "purchase of materials......."
        }]
        lines.extend(tradejournal)
    else:
        tradejournal = [{
            "gl_id": vendordata["defaulttradegl"],
            "amount": tradeamount,
            "description": "purchase of materials......."
        }]
        lines.extend(tradejournal)

    if OthChrg is not None and OthChrg != 0:
        OthChrgjournal = [{
            "gl_id": otherchargesgl,
            "amount": OthChrg,
            "description": "Other charges in GST einvoice"
        }]

        lines.extend(OthChrgjournal)

    if vendordata["defaultbalancegl"]== None:
        balancejournal = [{
            "gl_id": defaultbalancegl,
            "amount": balanceamount,
            "description": "purchase of materials......."
        }]
        lines.extend(balancejournal)
    else:
        balancejournal = [{
            "gl_id": vendordata["defaultbalancegl"],
            "amount": balanceamount,
            "description": "purchase of materials......."
        }]
        lines.extend(balancejournal)

    if decoded_data_list_json.get("ValDtls", {}).get("CesVal") is not None and decoded_data_list_json["ValDtls"]["CesVal"] != 0:
        cessjournal = [{
            "gl_id": cessinputgl,
            "amount": cessamount,
            "description": "purchase of materials......."
        }]
        lines.extend(cessjournal)

        


    # Payload for the journal entry
    payload = {
    "journal_description": "Posted from einvoice data",
    "abid": abid,
    "transaction_type":"bill",
    "source":"einvoice",
    "cpty_gstin": decoded_data_list_json["SellerDtls"]["Gstin"],
    "cptyid": vendordata["vendorid"],
    "cptyname": vendordata["lglNm"],
    "ref_no": decoded_data_list_json["DocDtls"]["No"],
    "irn": decoded_data_list_json["Irn"],
    "pos": decoded_data_list_json["BuyerDtls"]["Pos"],#yet to be updated in java
    "doc_type": decoded_data_list_json["DocDtls"]["Typ"],
    "doc_date": convert_to_mysql_date(decoded_data_list_json["DocDtls"]["Dt"]),
    "Status":decoded_data_list_json["Status"],#yet to be updated in java
    "version": decoded_data_list_json["Version"],
    "assvalgl": vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
    "cgstvalgl": cgstinputgl,#yet to be updated in java
    "sgstvalgl": sgstinputgl,#yet to be updated in java
    "igstvalgl": igstinputgl,#yet to be updated in java
    "cessvalgl": cessinputgl,#yet to be updated in java
    "rndoffamtgl": vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
    "othchrggl": otherchargesgl,#yet to be updated in java
    "defaulttradegl":vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
    "defaultbalancegl":vendordata["defaultbalancegl"] if vendordata["defaultbalancegl"] is not None else defaultbalancegl,#yet to be updated in java
    "assval": AssVal,
    "cgstval": cgstamount,
    "sgstval": sgstamount,
    "igstval": igstamount,
    "cessval": cessamount,
    "rndoffamt": RndOffAmt,
    "othchrg": OthChrg,
    "totinvval": TotInvVal,
    "is_itemize": "N",
    "is_input_availed": "Y",
    "is_rev_charge_2b_or_einv":decoded_data_list_json["TranDtls"].get("RegRev",'N'),
    "pan": vendordata["pan"],
    "lines": lines
}
    # 
    # "tdsgl": None,#get the data from vendordata later
    # "tdsrate": None,#get the data from vendordata later
    # "lowertdscertno": None,#get the data from vendordata later
    

    # Headers
    headers = {
        "Content-Type": "application/json"
    }
    # print(payload)
    # Make the POST request
    try:
        response = requests.post(url, json=payload, headers=headers)
        response.raise_for_status()  # Raise an error if the request failed
        print("Response:")
        print(response.json())  # Print the JSON response from the API
        journaldata=response.json()
        return journaldata
    except requests.exceptions.RequestException as e:
        print(f"An error occurred: {e}")

def record_einvoice(decoded_data_list_json):

    # Define the API endpoint
    url = "http://127.0.0.1:8000/einvoice"  # Update with your actual endpoint URL

    input_data ={
        "abid":abid,
        "journal_id":journaldata["journal_id"],
        # "cptyid":journaldata["journal_id"],#get proper response from vendor api
        "version":decoded_data_list_json["Version"],
        "AckNo":decoded_data_list_json["AckNo"],
        "AckDt":decoded_data_list_json["AckDt"],
        "Irn":decoded_data_list_json["Irn"],
        "SignedInvoice":decoded_data_list_json["SignedInvoice"],
        "SignedQRCode":decoded_data_list_json["SignedQRCode"],
        "Status":decoded_data_list_json["Status"],
    }
    # Make the POST request to the API
    try:
        response = requests.post(url, json=input_data)

        # Check if the request was successful
        response.raise_for_status()  # Raise an error for bad status codes (e.g., 404, 500)

        # Parse and print the JSON response
        response_data = response.json()
        print("Response:", response_data)

    except requests.exceptions.HTTPError as http_err:
        print(f"HTTP error occurred: {http_err}")  # Print HTTP error if occurred
    except requests.exceptions.RequestException as req_err:
        print(f"Request error occurred: {req_err}")  # Print other request errors  



#get gl master to know the gl id to be used
response = requests.get(f"http://127.0.0.1:8000/glmaster?abid={abid}")


# Initialize variables to store GL IDs
cgstinputgl = None
sgstinputgl = None
igstinputgl = None
cessinputgl = None
cgstrcmpayable = None
sgstrcmpayable = None
igstrcmpayable = None
cessrcmpayable = None
defaultbalancegl = None
defaulttradegl = None
otherchargesgl = None

if response.status_code == 200:
    # print(response.json())  # Print the response data as JSON
    response=response.json()
    for item in response:
        if item['default_gl_id'] == 100000000001:
            cgstinputgl= item["gl_id"]
        elif item['default_gl_id'] == 100000000002:
            sgstinputgl= item["gl_id"]
        elif item['default_gl_id'] == 100000000003:
            igstinputgl= item["gl_id"]
        elif item['default_gl_id'] == 100000000004:
            cessinputgl= item["gl_id"]
        elif item['default_gl_id'] == 100000000017:
            cgstrcmpayable= item["gl_id"]
        elif item['default_gl_id'] == 100000000018:
            sgstrcmpayable= item["gl_id"]
        elif item['default_gl_id'] == 100000000019:
            igstrcmpayable= item["gl_id"]
        elif item['default_gl_id'] == 100000000020:
            cessrcmpayable= item["gl_id"]
        elif item['default_gl_id'] == 100000000007:
            defaultbalancegl= item["gl_id"]
        elif item['default_gl_id'] == 100000000006:
            defaulttradegl= item["gl_id"]
        elif item['default_gl_id'] == 100000000021:
            otherchargesgl= item["gl_id"]
# if response.status_code == 404:
#     print("NO records found")  # Print the response data as JSON
# print(cessinputgl)

    for item in decoded_data_list:
        try:
            decoded_data_list_json = json.loads(item)

            vendordata = create_vendors(decoded_data_list_json)
            journaldata=create_journal_einv(decoded_data_list_json)
                    # Print the entire dictionary for debugging
            # print("Journal Data:", vendordata)  # Added this line to print the whole dictionary
            # print(journaldata["status_code"])
            if journaldata["status_code"]==200:
                record_einvoice(decoded_data_list_json)

        
        except (IndexError, KeyError) as e:
            print(f"Error processing item: {e}")
