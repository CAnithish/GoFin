
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


def calculate_totals(items):
    """
    Calculate the sum of sgst, cgst, txval, cess, and igst from the provided items.

    Args:
        items: A list of dictionaries containing invoice item data.

    Returns:
        A dictionary with total values for sgst, cgst, txval, cess, and igst.
    """
    totals = {
        "sgst": 0,
        "cgst": 0,
        "txval": 0,
        "cess": 0,
        "igst": 0
    }

    for item in items:
        totals["sgst"] += item.get("sgst", 0)
        totals["cgst"] += item.get("cgst", 0)
        totals["txval"] += item.get("txval", 0)
        totals["cess"] += item.get("cess", 0)
        totals["igst"] += item.get("igst", 0)

    return totals
            
# Open the JSON file
with open("returns_R2B_33FULPS4391L1ZU_012024.json", "r") as file:
    json_data = json.load(file)  # Use json.load for reading a file

# Print the data
# print(json_data)
abid = 1000000000 # get this from cookie

rtnprd_2b = json_data["data"]["rtnprd"]
docdata = json_data["data"]["docdata"]
# print(docdata)


    





def create_vendors(input):
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
        "lglNm": input.get("trdnm", None), #later change and save this to trade name
        "gstin": input.get("ctin", None),
        "defaulttradegl":defaulttradegl,
        "defaultbalancegl":defaultbalancegl
    }

    # Query parameters
    
    gstin = input.get("ctin", None)

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


def create_journal_2b_b2b(input):
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
    url = "http://localhost:8000/create_journal_gstr2b"  # Replace with your actual server URL

    

    cpty_gstin=input["ctin"]
    

    for item in input["inv"]:
        
        lines = []
        journal_responses = []  # List to hold responses for each invoice


        # print(type(len(item.get("items"))))
        # print(len(item.get("items")))
        # Calculate totals for each invoice item
        if item.get("items"):
            totals = calculate_totals(item.get("items"))
            tradeamount = totals["txval"]
            cgstamount = totals["cgst"]
            sgstamount = totals["sgst"]
            igstamount = totals["igst"]
            cessamount = totals["cess"]
            
        
        else:
            tradeamount = item.get("txval", 0)
            cgstamount = item.get("cgst", 0)
            sgstamount = item.get("sgst", 0)
            igstamount = item.get("igst", 0)
            cessamount = item.get("cess", 0)

        val= item.get("val",0) #this includes other charges in GSTR2b
        balanceamount = -val
        OthChrg = val - (tradeamount+cgstamount+sgstamount+igstamount+cessamount)


        if item.get("rev")=="Y":
            
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


        # GST entry creation - input eligibility check done, if  ineligible then it is added to expense gl.
        if item.get("itcavl")=='Y':
            if cgstamount is not None and cgstamount != 0:
                cgstjournal = [{
                    "gl_id": cgstinputgl,
                    "amount": cgstamount,
                    "description": "GST input availed"
                },
                {
                    "gl_id": sgstinputgl,
                    "amount": sgstamount,
                    "description": "GST input availed"
                }]

                lines.extend(cgstjournal)
            elif igstamount is not None and igstamount != 0:
                igstjournal = [{
                    "gl_id": igstinputgl,
                    "amount": igstamount,
                    "description": "GST input availed"
                }]
                lines.extend(igstjournal)

            if cessamount is not None and cessamount != 0:
                cessjournal = [{
                    "gl_id": cessinputgl,
                    "amount": cessamount,
                    "description": "GST input availed"
                }]
                lines.extend(cessjournal)

            if vendordata["defaulttradegl"] == None:
                tradejournal = [{
                    "gl_id": defaulttradegl,
                    "amount": tradeamount,
                    "description": "GST input availed"
                }]
                lines.extend(tradejournal)
            else:
                tradejournal = [{
                    "gl_id": vendordata["defaulttradegl"],
                    "amount": tradeamount,
                    "description": "GST input availed"
                }]
                lines.extend(tradejournal)
        else:
            if vendordata["defaulttradegl"] == None:
                tradejournal = [{
                    "gl_id": defaulttradegl,
                    "amount": tradeamount+cessamount+igstamount+cgstamount+sgstamount,
                    "description": "GST input not availed"
                }]
                lines.extend(tradejournal)
            else:
                tradejournal = [{
                    "gl_id": vendordata["defaulttradegl"],
                    "amount": tradeamount+cessamount+igstamount+cgstamount+sgstamount,
                    "description": "GST input not availed"
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
                "description": "purchase of materials.......",
                "cptyid": vendordata["vendorid"]
            }]
            lines.extend(balancejournal)
        else:
            balancejournal = [{
                "gl_id": vendordata["defaultbalancegl"],
                "amount": balanceamount,
                "description": "purchase of materials.......",
                "cptyid": vendordata["vendorid"]
            }]
            lines.extend(balancejournal)



        # if item.get("itcavl") is not None and item.get("itcavl") == "Y":
        #     is_input_availed = 
        #     cessjournal = [{
        #         "gl_id": 100000000004,
        #         "amount": cessamount,
        #         "description": "purchase of materials......."
        #     }]
        #     lines.extend(cessjournal)


        # Payload for the journal entry
        payload = {
        "journal_description": "Posted from GSTR2B data",
        "abid": abid,
        "transaction_type":"bill",
        "source":"gstr2b",
        "cpty_gstin": cpty_gstin,
        "cptyid": vendordata["vendorid"],
        "cptyname": vendordata["lglNm"],
        "ref_no": item.get("inum"),
        "irn": item.get("irn","NOTAVL"),
        "pos": item.get("pos","NOTAVL"),
        "doc_type": "INV",
        "doc_date": convert_to_mysql_date(item.get("dt")),
        "assval": tradeamount,
        "cgstval": cgstamount,
        "sgstval": sgstamount,
        "igstval": igstamount,
        "cessval": cessamount,
        "rndoffamt": 0,
        "othchrg": 0,
        "totinvval": val,
        "assvalgl": vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
        "cgstvalgl": cgstinputgl,#yet to be updated in java
        "sgstvalgl": sgstinputgl,#yet to be updated in java
        "igstvalgl": igstinputgl,#yet to be updated in java
        "cessvalgl": cessinputgl,#yet to be updated in java
        "rndoffamtgl": vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
        "othchrggl": otherchargesgl,#yet to be updated in java
        "defaulttradegl":vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
        "defaultbalancegl":vendordata["defaultbalancegl"] if vendordata["defaultbalancegl"] is not None else defaultbalancegl,#yet to be updated in java
        "is_itemize": "N",
        "is_input_availed": item.get("itcavl"),#for now availing all input eligible ingstr2b, later check vendor master data if user marked it as ineligible
        "is_input_eligible_2b":item.get("itcavl"),
        "rtnprd_2b": rtnprd_2b,
        "is_rev_charge_2b_or_einv":item.get("rev"),
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

        # Make the POST request
        try:
            response = requests.post(url, json=payload, headers=headers)
            response.raise_for_status()  # Raise an error if the request failed
            print("Response:")
            print(response.json())  # Print the JSON response from the API
            journal_responses.append(response.json())  # Store response for each invoice
            # journaldata=response.json()
            # return journaldata
        except requests.exceptions.RequestException as e:
            print(f"An error occurred: {e}")

    return journal_responses  # Return all journal responses after processing all items

def create_journal_2b_cdnr(input):
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
    url = "http://localhost:8000/create_journal_gstr2b"  # Replace with your actual server URL

    

    cpty_gstin=input["ctin"]
    

    for item in input["nt"]:
        
        lines = []
        journal_responses = []  # List to hold responses for each invoice

        if item["typ"] =='C':
            balanceamount = item.get("val", 0)
            val= -item.get("val",0)
            doc_type='CRN'
            # Calculate totals for each invoice item
            if item.get("items"):#until Sep 2024 format is different. From Sep 2024 format is different.
                totals = calculate_totals(item.get("items"))
                tradeamount = -totals["txval"]
                cgstamount = -totals["cgst"]
                sgstamount = -totals["sgst"]
                igstamount = -totals["igst"]
                cessamount = -totals["cess"]
                
            
            else:
                tradeamount = -item.get("txval", 0)
                cgstamount = -item.get("cgst", 0)
                sgstamount = -item.get("sgst", 0)
                igstamount = -item.get("igst", 0)
                cessamount = -item.get("cess", 0)

            
        else:
            balanceamount = -item.get("val", 0)
            val= item.get("val",0)
            doc_type='DBN'

            # Calculate totals for each invoice item
            if item.get("items"):
                totals = calculate_totals(item.get("items"))
                tradeamount = totals["txval"]
                cgstamount = totals["cgst"]
                sgstamount = totals["sgst"]
                igstamount = totals["igst"]
                cessamount = totals["cess"]
                
            
            else:
                tradeamount = item.get("txval", 0)
                cgstamount = item.get("cgst", 0)
                sgstamount = item.get("sgst", 0)
                igstamount = item.get("igst", 0)
                cessamount = item.get("cess", 0)

        OthChrg = val - (tradeamount+cgstamount+sgstamount+igstamount+cessamount)

        if item.get("rev")=="Y":

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
        # GST entry creation - input eligibility check done, if  ineligible then it is added to expense gl.
        if item.get("itcavl")=='Y':
            if cgstamount is not None and cgstamount != 0:
                cgstjournal = [{
                    "gl_id": cgstinputgl,
                    "amount": cgstamount,
                    "description": "GST input availed"
                },
                {
                    "gl_id": sgstinputgl,
                    "amount": sgstamount,
                    "description": "GST input availed"
                }]

                lines.extend(cgstjournal)
            elif igstamount is not None and igstamount != 0:
                igstjournal = [{
                    "gl_id": igstinputgl,
                    "amount": igstamount,
                    "description": "GST input availed"
                }]
                lines.extend(igstjournal)

            if cessamount is not None and cessamount != 0:
                cessjournal = [{
                    "gl_id": cessinputgl,
                    "amount": cessamount,
                    "description": "GST input availed"
                }]
                lines.extend(cessjournal)

            if vendordata["defaulttradegl"] == None:
                tradejournal = [{
                    "gl_id": defaulttradegl,
                    "amount": tradeamount,
                    "description": "GST input availed"
                }]
                lines.extend(tradejournal)
            else:
                tradejournal = [{
                    "gl_id": vendordata["defaulttradegl"],
                    "amount": tradeamount,
                    "description": "GST input availed"
                }]
                lines.extend(tradejournal)
        else:
            if vendordata["defaulttradegl"] == None:
                tradejournal = [{
                    "gl_id": defaulttradegl,
                    "amount": tradeamount+cessamount+igstamount+cgstamount+sgstamount,
                    "description": "GST input not availed"
                }]
                lines.extend(tradejournal)
            else:
                tradejournal = [{
                    "gl_id": vendordata["defaulttradegl"],
                    "amount": tradeamount+cessamount+igstamount+cgstamount+sgstamount,
                    "description": "GST input not availed"
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

        

        # if item.get("itcavl") is not None and item.get("itcavl") == "Y":
        #     is_input_availed = 
        #     cessjournal = [{
        #         "gl_id": 100000000004,
        #         "amount": cessamount,
        #         "description": "purchase of materials......."
        #     }]
        #     lines.extend(cessjournal)


        # Payload for the journal entry
        payload = {
        "journal_description": "Posted from GSTR2B data",
        "abid": abid,
        "transaction_type":"bill",
        "source":"gstr2b",
        "cpty_gstin": cpty_gstin,
        "cptyid": vendordata["vendorid"],
        "cptyname": vendordata["lglNm"],
        "ref_no": item.get("ntnum"),
        "irn": item.get("irn","NOTAVL"),
        "doc_type": doc_type,
        "doc_date": convert_to_mysql_date(item.get("dt")),
        "assval": tradeamount,
        "cgstval": cgstamount,
        "sgstval": sgstamount,
        "igstval": igstamount,
        "cessval": cessamount,
        "rndoffamt": 0,
        "othchrg": 0,
        "totinvval": val,
        "assvalgl": vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
        "cgstvalgl": cgstinputgl,#yet to be updated in java
        "sgstvalgl": sgstinputgl,#yet to be updated in java
        "igstvalgl": igstinputgl,#yet to be updated in java
        "cessvalgl": cessinputgl,#yet to be updated in java
        "rndoffamtgl": vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
        "othchrggl": otherchargesgl,#yet to be updated in java
        "defaulttradegl":vendordata["defaulttradegl"] if vendordata["defaulttradegl"] is not None else defaulttradegl,#yet to be updated in java
        "defaultbalancegl":vendordata["defaultbalancegl"] if vendordata["defaultbalancegl"] is not None else defaultbalancegl,#yet to be updated in java        
        "is_itemize": "N",
        "is_input_availed": item.get("itcavl"),#for now availing all input eligible ingstr2b, later check vendor master data if user marked it as ineligible
        "is_input_eligible_2b":item.get("itcavl"),
        "rtnprd_2b": rtnprd_2b,
        "is_rev_charge_2b_or_einv":item.get("rev"),
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

        # Make the POST request
        try:
            response = requests.post(url, json=payload, headers=headers)
            response.raise_for_status()  # Raise an error if the request failed
            print("Response:")
            print(response.json())  # Print the JSON response from the API
            journal_responses.append(response.json())  # Store response for each invoice
            # journaldata=response.json()
            # return journaldata
        except requests.exceptions.RequestException as e:
            print(f"An error occurred: {e}")

    return journal_responses  # Return all journal responses after processing all items


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

    if docdata.get("b2b"):
        for item in docdata["b2b"]:
            try:
                vendordata=create_vendors(item)
                create_journal_2b_b2b(item)


            except (IndexError, KeyError) as e:
                print(f"Error processing item: {e}")


    if docdata.get("cdnr"):
        for item in docdata["cdnr"]:
            try:
                vendordata=create_vendors(item)
                create_journal_2b_cdnr(item)


            except (IndexError, KeyError) as e:
                print(f"Error processing item: {e}")




# def record_einvoice(decoded_data_list_json):

#     # Define the API endpoint
#     url = "http://127.0.0.1:8000/einvoice"  # Update with your actual endpoint URL

#     input_data ={
#         "abid":abid,
#         "journal_id":journaldata["journal_id"],
#         # "cptyid":journaldata["journal_id"],#get proper response from vendor api
#         "version":decoded_data_list_json["Version"],
#         "AckNo":decoded_data_list_json["AckNo"],
#         "AckDt":decoded_data_list_json["AckDt"],
#         "Irn":decoded_data_list_json["Irn"],
#         "SignedInvoice":decoded_data_list_json["SignedInvoice"],
#         "SignedQRCode":decoded_data_list_json["SignedQRCode"],
#         "Status":decoded_data_list_json["Status"],
#     }
#     # Make the POST request to the API
#     try:
#         response = requests.post(url, json=input_data)

#         # Check if the request was successful
#         response.raise_for_status()  # Raise an error for bad status codes (e.g., 404, 500)

#         # Parse and print the JSON response
#         response_data = response.json()
#         print("Response:", response_data)

#     except requests.exceptions.HTTPError as http_err:
#         print(f"HTTP error occurred: {http_err}")  # Print HTTP error if occurred
#     except requests.exceptions.RequestException as req_err:
#         print(f"Request error occurred: {req_err}")  # Print other request errors  


# for item in decoded_data_list:
#     try:
#         decoded_data_list_json = json.loads(item)

#         vendordata = create_vendors(decoded_data_list_json)
#         journaldata=create_journal_einv(decoded_data_list_json)
#         # print(journaldata["status_code"])
#         if journaldata["status_code"]==200:
#             record_einvoice(decoded_data_list_json)
#         # if vendordata:
#         #     print(vendordata)  # Use vendordata outside the function
       
#     except (IndexError, KeyError) as e:
#         print(f"Error processing item: {e}")

