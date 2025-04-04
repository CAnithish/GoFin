import requests
import json

# Define the endpoint URL
url = "http://127.0.0.1:8000/create_journal_manual"

# Prepare the data to be sent
data = {
    "journal_description": "Sample journal entry",
    "abid": 1000000000,
    "transaction_type": "bill",
    "source": "manual",
    "cpty_gstin": "33AALCA0171E1Z6",
    "cptyid": 100000000002,
    "cptyname": "Appario Retail Private Ltd",
    "ref_no": "51346",
    "irn": "bcvb",
    "doc_type": "INV",
    "doc_date": "2025-01-03",
    "version": "1",
    "assval": 1500,
    "cgstval": 135,
    "sgstval": 135,
    "igstval": 0,
    "cessval": 0,
    "rndoffamt": -0.25,
    "othchrg": 125.25,
    "totinvval": 1895,
    "is_itemize":  'N',
    "is_input_availed": 'Y',
    "is_input_eligible_2b": 'N',
    # Add more fields as necessary
    "lines": [
        {"gl_id": 100000000100, "amount": 1000.23},
        {"gl_id": 100000000095, "amount": 90},
        {"gl_id": 100000000096, "amount": 90},
        {"gl_id": 100000000140, "amount": 523},
        {"gl_id": 100000000101, "amount": -1433.23},
        {"gl_id": 100000000111, "amount": -90},
        {"gl_id": 100000000112, "amount": -90}
        # Add more lines as necessary
    ]
}

# Send the POST request
response = requests.post(url, json=data)

# Check the response
if response.json()['status_code'] == 200:
    print(response.json())
elif response.json()['status_code'] == 403:
    print(response.json())
else:
    print(f"Error saving data: {response.status_code} - {response.text}")
