from fastapi import FastAPI
from datetime import date
import asyncio
import cProfile
import pstats
import time
import logging
app = FastAPI()
from datetime import datetime



from fastapi import FastAPI, HTTPException, Query
from typing import Optional, List
from sqlalchemy import create_engine, Column, Integer, String, Float, DateTime, Date, func, Table, MetaData, select, Identity, text, ForeignKey,delete,insert,update
from sqlalchemy.orm import sessionmaker, Session
from sqlalchemy.ext.declarative import declarative_base
from pydantic import BaseModel, create_model, Field  # Ensure this is imported

# Database setup
engine = create_engine('mysql+pymysql://root:Keerthi*1@localhost/userinputtest') #, echo=True)  # Logs all queries
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
metadata = MetaData()  # No bind argument here
metadata.reflect(bind=engine)



@app.get("/glmaster")
async def get_gllist_by_abid(abid: int):
    """
    Dynamically retrieves all columns and their values for vendors with the given abid and gstin in JSON format.
    """
    try:
        # Reflect the table
        gl_table = Table("gl_master", metadata, autoload_with=engine)

        # Open database session
        session = SessionLocal()

        # Build the query
        query = select(gl_table).where(
            gl_table.c.abid == abid
        )

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()  # Ensure session is closed
            raise HTTPException(
                status_code=404,
                detail=f"No GL found for abid={abid}"
            )

        # Convert the result to a list of dictionaries
        response = [
            {column.name: value for column, value in zip(gl_table.columns, row)}
            for row in result
        ]
        session.close()
        return response

    except HTTPException as e:
        raise e  # Explicitly re-raise HTTP exceptions like 404

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error fetching GLmaster data: {str(e)}")


# Pydantic model for input validation
class GLMasterCreate(BaseModel):
    gl_name: str
    gl_nature: str
    gl_grouping: Optional[str] = None
    description: Optional[str] = None
    abid: int
    type: str

@app.post("/glmaster")
async def create_gl_master(gl_master: GLMasterCreate):
    """
    Inserts a new record into the gl_master table with the provided details,
    if a record with the same combination does not already exist.
    """
    try:
        # Reflect the table
        gl_table = Table("gl_master", metadata, autoload_with=engine)

        # Open database session
        session = SessionLocal()

        # Check for existing record with the same combination
        query = select(gl_table).where(
            (gl_table.c.gl_name == gl_master.gl_name) &
            (gl_table.c.gl_nature == gl_master.gl_nature) &
            (gl_table.c.abid == gl_master.abid)
        )

        existing_record = session.execute(query).fetchone()

        if existing_record:
            return {"status_code": 403,"message": "A record with this combination already exists(name & nature)."}

        # Prepare the insert statement
        stmt = insert(gl_table).values(
            gl_name=gl_master.gl_name,
            gl_nature=gl_master.gl_nature,
            gl_grouping=gl_master.gl_grouping,
            description=gl_master.description,
            abid=gl_master.abid,
            type=gl_master.type,
            created_at=datetime.now()  # Automatically set created_at to now
        )

        # Execute the insert statement
        session.execute(stmt)
        session.commit()  # Commit the transaction

        return {"status_code": 200,"message": "GL master record created successfully."}

    except Exception as e:
        session.rollback()  # Rollback in case of error
        raise HTTPException(status_code=500, detail=f"Error creating GL master record: {str(e)}")

    finally:
        session.close()  # Ensure session is closed after operation

# Pydantic model for input validation
class GLMasterUpdate(BaseModel):
    gl_id: int  # Assuming gl_id is an integer
    gl_name: Optional[str] = None
    gl_grouping: Optional[str] = None
    description: Optional[str] = None
    abid: int  # Include abid in the request body

@app.put("/glmaster")
async def update_gl_master(gl_master: GLMasterUpdate):
    """
    Updates an existing record in the gl_master table with the provided details.
    Only the fields provided in the request will be updated.
    """
    try:
        # Open database session
        session = SessionLocal()

        # Reflect the table
        gl_table = Table("gl_master", metadata, autoload_with=engine)

        # Prepare the update statement with both gl_id and abid in the WHERE clause
        stmt = update(gl_table).where(
            (gl_table.c.gl_id == gl_master.gl_id) & 
            (gl_table.c.abid == gl_master.abid)
        )

        # Update only if fields are provided
        if gl_master.gl_name is not None:
            stmt = stmt.values(gl_name=gl_master.gl_name)
        if gl_master.gl_grouping is not None:
            stmt = stmt.values(gl_grouping=gl_master.gl_grouping)
        if gl_master.description is not None:
            stmt = stmt.values(description=gl_master.description)

        # Execute the update statement
        result = session.execute(stmt)

        if result.rowcount == 0:
            raise HTTPException(status_code=404, detail="Record not found")

        session.commit()  # Commit the transaction

        return {"status_code": 200, "message": "GL master record updated successfully."}

    except Exception as e:
        session.rollback()  # Rollback in case of error
        raise HTTPException(status_code=500, detail=f"Error updating GL master record: {str(e)}")

    finally:
        session.close()  # Ensure session is closed after operation


@app.delete("/glmaster")
async def delete_gl_master(gl_id: int, abid: int):
    """
    Deletes a record from the gl_master table based on gl_id and abid.
    Before deleting, it checks if there are any entries in the journal_lines table.
    
    :param gl_id: The ID of the GL master record to be deleted.
    :param abid: The associated ABID.
    """
    session = SessionLocal()
    
    try:
        # Reflect the journal_lines table
        journal_lines_table = Table("journal_lines_v2", metadata, autoload_with=engine)

        # Check for existing records in journal_lines with the given gl_id and abid
        check_query = select(journal_lines_table).where(
            (journal_lines_table.c.gl_id == gl_id) & 
            (journal_lines_table.c.abid == abid)
        )
        existing_journal_lines = session.execute(check_query).fetchall()

        if existing_journal_lines:
            return {"status_code": 403, "message": "Cannot delete GL master record; associated journal lines exist."}
            # raise HTTPException(status_code=403, detail="Cannot delete GL master record; associated journal lines exist.")
        
        # Proceed to delete from gl_master
        delete_query = delete(gl_master).where(
            (gl_master.c.gl_id == gl_id) & 
            (gl_master.c.abid == abid)
        )

        result = session.execute(delete_query)

        if result.rowcount == 0:
            return {"status_code": 404, "message": "GL master record not found"}
            # raise HTTPException(status_code=404, detail="GL master record not found")

        session.commit()  # Commit the transaction

        return {"status_code": 200, "message": "GL master record deleted successfully."}

    except Exception as e:
        session.rollback()  # Rollback in case of error
        raise HTTPException(status_code=500, detail=f"Error deleting GL master record: {str(e)}")

    finally:
        session.close()  # Ensure session is closed after operation


# Autoload tables
gl_master = Table("gl_master", metadata, autoload_with=engine)
default_gl_master = Table("default_gl_master", metadata, autoload_with=engine)


@app.get("/defaultglmaster")
async def get_all_default_gl_records():
    """
    Fetch all records from the default_gl_master table.
    Optionally supports filters in the future.
    """
    try:
        # Open database session
        session = SessionLocal()

        # Build the query to fetch all records
        query = select(default_gl_master)

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()  # Ensure session is closed
            raise HTTPException(
                status_code=404,
                detail="No default GL records found."
            )

        # Convert the result to a list of dictionaries
        response = [
            {column.name: value for column, value in zip(default_gl_master.columns, row)}
            for row in result
        ]
        session.close()
        return response

    except HTTPException as e:
        raise e  # Explicitly re-raise HTTP exceptions like 404

    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error fetching default GLmaster data: {str(e)}"
        )


# Pydantic Models
class GLMasterCreate(BaseModel):
    gl_name: Optional[str] = None
    gl_nature: Optional[str] = None
    gl_grouping: Optional[str] = None
    description: Optional[str] = None
    abid: int
    type: Optional[str] = None
    default_gl_id: Optional[int] = None
    org_type: Optional[str] = None
    tds_or_tcs_section: Optional[str] = None
    tds_or_tcs_rate: Optional[int] = None

class DefaultGLMasterResponse(BaseModel):
    default_gl_id: int
    default_gl_name: Optional[str] = None
    default_gl_nature: Optional[str] = None
    default_gl_grouping: Optional[str] = None
    description: Optional[str] = None
    type: Optional[str] = None
    org_type: Optional[str] = None
    tds_or_tcs_section: Optional[str] = None
    tds_or_tcs_rate: Optional[int] = None

class ImportGLMasterRequest(BaseModel):
    abid: int
    default_gl_ids: List[int]



@app.post("/import-gl-master")
async def import_gl_master(request: ImportGLMasterRequest):
    """
    Import records from default_gl_master to gl_master based on the provided default_gl_ids.
    
    :param request: The request body containing abid and list of default_gl_ids.
    """
    
    db: Session = SessionLocal()
    
    try:
        # Fetch records from default_gl_master based on provided IDs
        query = select(default_gl_master).where(default_gl_master.c.default_gl_id.in_(request.default_gl_ids))
        results = db.execute(query).fetchall()
        
        if not results:
            raise HTTPException(status_code=404, detail="No records found in default_gl_master")

        # Prepare new records for gl_master
        new_records = []
        for record in results:
            new_record = {
                "gl_name": record.default_gl_name,
                "gl_nature": record.default_gl_nature,
                "gl_grouping": record.default_gl_grouping,
                "description": record.description,
                "abid": request.abid,
                "type": record.type,
                "default_gl_id": record.default_gl_id,
                "org_type": record.org_type,
                "tds_or_tcs_section": record.tds_or_tcs_section,
                "tds_or_tcs_rate": record.tds_or_tcs_rate,
            }
            new_records.append(new_record)

        # Add new records to gl_master using insert statement
        insert_query = gl_master.insert().values(new_records)
        db.execute(insert_query)
        db.commit()  # Commit the transaction

        return {"status_code": 200, "message": "Records imported successfully."}

    except Exception as e:
        db.rollback()  # Rollback in case of error
        raise HTTPException(status_code=500, detail=f"Error importing records: {str(e)}")

    finally:
        db.close()  # Ensure session is closed after operation

# Create database tables (if needed) - Uncomment if running for the first time.
# metadata.create_all(bind=engine)  # Uncomment this line if you need to create tables.


# Pydantic model for response structure
class TrialBalanceItem(BaseModel):
    gl_id: int
    total_amount: float
    gl_name: Optional[str] = None
    gl_nature: Optional[str] = None
    gl_grouping: Optional[str] = None
    description: Optional[str] = None
    type: Optional[str] = None
    org_type: Optional[str] = None
    tds_or_tcs_section: Optional[str] = None
    tds_or_tcs_rate: Optional[int] = None

@app.get("/trial_balance", response_model=List[TrialBalanceItem])
async def get_trial_balance(abid: int):
    """
    Fetch the total sum of amounts for each unique gl_id based on the provided abid,
    along with additional information from gl_master.
    """
    db = SessionLocal()
    try:
        # Query to get total sum of amount for each unique gl_id with additional info from gl_master
        results = db.execute(
            text("""
                SELECT 
                    j.gl_id,
                    SUM(j.amount) AS total_amount,
                    g.gl_name,
                    g.gl_nature,
                    g.gl_grouping,
                    g.description,
                    g.type,
                    g.org_type,
                    g.tds_or_tcs_section,
                    g.tds_or_tcs_rate
                FROM 
                    journal_lines_v2 j
                LEFT JOIN 
                    gl_master g ON j.gl_id = g.gl_id
                WHERE 
                    j.abid = :abid
                GROUP BY 
                    j.gl_id, g.gl_name, g.gl_nature, g.gl_grouping, 
                    g.description, g.type, g.org_type, 
                    g.tds_or_tcs_section, g.tds_or_tcs_rate;
            """),
            {"abid": abid}
        ).fetchall()

        # Prepare response
        trial_balance_report = [
            {
                "gl_id": row[0],
                "total_amount": row[1],
                "gl_name": row[2],
                "gl_nature": row[3],
                "gl_grouping": row[4],
                "description": row[5],
                "type": row[6],
                "org_type": row[7],
                "tds_or_tcs_section": row[8],
                "tds_or_tcs_rate": row[9]
            } for row in results
        ]
        
        return trial_balance_report

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    
    finally:
        db.close()
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#Vendor related API's
@app.get("/vendorlist")
async def get_vendor_by_abid(abid: int):
    """
    Dynamically retrieves all columns and their values for vendors with the given abid and gstin in JSON format.
    """
    try:
        # Reflect the table
        vendor_table = Table("vendorlist", metadata, autoload_with=engine)

        # Open database session
        session = SessionLocal()

        # Build the query
        query = select(vendor_table).where(
            vendor_table.c.abid == abid
        )

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()  # Ensure session is closed
            raise HTTPException(
                status_code=404,
                detail=f"No vendors found for abid={abid}"
            )

        # Convert the result to a list of dictionaries
        response = [
            {column.name: value for column, value in zip(vendor_table.columns, row)}
            for row in result
        ]
        session.close()
        return response

    except HTTPException as e:
        raise e  # Explicitly re-raise HTTP exceptions like 404

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error fetching vendor data: {str(e)}")

#Update/put vendor data------------------
# Reflect the vendortable
vendortable = Table("vendorlist", metadata, autoload_with=engine)

# Pydantic model for input data
class VendorUpdate(BaseModel):
    abid: int
    vendorid: int
    is_trans_similar: Optional[str] = None
    defaultbalancegl: Optional[int] = None
    defaulttradegl: Optional[int] = None
    lglNm: Optional[str] = None
    gstin: Optional[str] = None
    pan: Optional[str] = None
    tdssection: Optional[str] = None
    tdsrate: Optional[int] = None
    addr1: Optional[str] = None
    addr2: Optional[str] = None
    addr3: Optional[str] = None
    loc: Optional[str] = None
    pin: Optional[int] = None
    stcd: Optional[str] = None
    email: Optional[str] = None
    mobile: Optional[int] = None
    phone: Optional[int] = None
    bankac: Optional[str] = None
    ifsc: Optional[str] = None
    lowertdsrate: Optional[int] = None
    lowertdscertno: Optional[str] = None

@app.put("/update-vendor")
async def update_vendor(data: VendorUpdate):
    try:
        # Open a database session
        session = SessionLocal()

        # # Build the update query dynamically
        # update_data = {key: value for key, value in data.dict().items() if value is not None and key not in ["abid", "vendorid"]}
        # Include all fields, even if they are None
        update_data = {
            key: (None if value == "" else value)  # Map empty strings to None
            for key, value in data.dict(exclude_unset=True).items()
            if key not in ["abid", "vendorid"]
        }
        if not update_data:
            raise HTTPException(status_code=400, detail="No fields to update provided.")

        query = (
            update(vendortable)
            .where(vendortable.c.abid == data.abid, vendortable.c.vendorid == data.vendorid)
            .values(**update_data)
        )

        # Execute the query
        result = session.execute(query)
        session.commit()

        # Check if the record was updated
        if result.rowcount == 0:
            raise HTTPException(
                status_code=404,
                detail=f"Vendor with abid={data.abid} and vendorid={data.vendorid} not found."
            )

        return {"status_code": 200, "message": "Vendor updated successfully.", "updated_fields": update_data}

    except Exception as e:
        session.rollback()
        raise HTTPException(status_code=500, detail=f"Error updating vendor: {str(e)}")

    finally:
        session.close()



class VendorInput(BaseModel):
    lglNm: str
    addr1: Optional[str] = None
    addr2: Optional[str] = None
    addr3: Optional[str] = None
    loc: Optional[str] = None
    pin: Optional[int] = None
    stcd: Optional[str] = None
    gstin: Optional[str] = None
    defaulttradegl: Optional[int] = None
    defaultbalancegl: Optional[int] = None


@app.post("/vendorlist") # for GST e invoice only
async def create_vendor(
    abid: int = Query(..., description="Account ID"),
    vendor: Optional[VendorInput] = None
    ):
    """
    Creates a new vendor entry in the database or returns an existing one.

    Args:
        abid (int): The account ID to associate the vendor with.
        gstin (str): GSTIN of the vendor.
        vendor (VendorInput): Vendor data to create.

    Returns:
        dict: JSON response with vendor details.
    """
    try:
        session = SessionLocal()

        # Reflect the table from the database
        vendor_table = Table("vendorlist", metadata, autoload_with=engine)

        # Check if the vendor already exists
        query = session.query(vendor_table).filter(
            vendor_table.c.abid == abid,
            vendor_table.c.gstin == vendor.gstin
        )
        existing_vendor = query.first()

        if existing_vendor:
            # Convert the existing vendor to a dictionary
            existing_vendor_dict = {col.name: getattr(existing_vendor, col.name) for col in vendor_table.columns}
            session.close()
            return {
                "status_code": 403,
                "message": "Vendor with the provided abid and gstin already exists.",
                "existing_entry": existing_vendor_dict
            }

        # Insert the vendor data into the table
        stmt = insert(vendor_table).values(
            abid=abid,
            gstin=vendor.gstin,
            pan=vendor.gstin[2:12],
            lglNm=vendor.lglNm,
            addr1=vendor.addr1,
            addr2=vendor.addr2,
            addr3=vendor.addr3,
            loc=vendor.loc,
            pin=vendor.pin,
            stcd=vendor.stcd,
            defaulttradegl = vendor.defaulttradegl,
            defaultbalancegl = vendor.defaultbalancegl,
            is_trans_similar = "y"
            
        )
        session.execute(stmt)
        session.commit()

        # Fetch the newly created vendor
        new_vendor = session.query(vendor_table).filter(
            vendor_table.c.abid == abid,
            vendor_table.c.gstin == vendor.gstin
        ).first()
        new_vendor_dict = {col.name: getattr(new_vendor, col.name) for col in vendor_table.columns}

        session.close()
        return {
            "status_code": 200,
            "message": "Vendor created successfully.",
            "new_entry": new_vendor_dict
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error creating vendor: {e}")


#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------

customer_table = Table("customerlist", metadata, autoload_with=engine)

#customer related API's
@app.get("/customerlist")
async def get_customer_by_abid(abid: int):
    """
    Dynamically retrieves all columns and their values for vendors with the given abid and gstin in JSON format.
    """
    try:
        # Reflect the table
        customer_table = Table("customerlist", metadata, autoload_with=engine)

        # Open database session
        session = SessionLocal()

        # Build the query
        query = select(customer_table).where(
            customer_table.c.abid == abid
        )

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()  # Ensure session is closed
            raise HTTPException(
                status_code=404,
                detail=f"No Customers found for abid={abid}"
            )

        # Convert the result to a list of dictionaries
        response = [
            {column.name: value for column, value in zip(customer_table.columns, row)}
            for row in result
        ]
        session.close()
        return response

    except HTTPException as e:
        raise e  # Explicitly re-raise HTTP exceptions like 404

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error fetching customer data: {str(e)}")

class CustomerInput(BaseModel):
    abid: int
    lglNm: str  # Required field, change this if you want to make it optional
    addr1: Optional[str] = None
    addr2: Optional[str] = None
    addr3: Optional[str] = None
    loc: Optional[str] = None
    pin: Optional[int] = None
    stcd: Optional[str] = None
    gstin: Optional[str] = None
    defaultbalancegl: Optional[int] = None
    pan: Optional[str] = None
    tdssection: Optional[str] = None
    tdsrate: Optional[float] = None
    email: Optional[str] = None
    mobile: Optional[str] = None
    phone: Optional[str] = None
    bankac: Optional[str] = None
    ifsc: Optional[str] = None

@app.post("/customerlist")  # for GST e-invoice only
async def create_vendor(
    customer: CustomerInput
):
    try:
        session = SessionLocal()

        # Reflect the table from the database
        customer_table = Table("customerlist", metadata, autoload_with=engine)

        # Validation checks
        if customer.gstin and customer.gstin != "":
            query = session.query(customer_table).filter(
                customer_table.c.abid == customer.abid,
                customer_table.c.gstin == customer.gstin
            )
        elif customer.mobile and customer.mobile != "":
            query = session.query(customer_table).filter(
                customer_table.c.abid == customer.abid,
                customer_table.c.mobile == customer.mobile
            )
        existing_customer = query.first()

        if existing_customer:
            existing_customer_dict = {col.name: getattr(existing_customer, col.name) for col in customer_table.columns}
            session.close()
            return {
                "status_code": 403,
                "message": "Customer with the provided abid and gstin already exists.",
                "existing_entry": existing_customer_dict
            }

        # Insert the vendor data into the table
        stmt = insert(customer_table).values(
            abid=customer.abid,
            gstin=customer.gstin,
            pan=customer.pan if customer.pan else customer.gstin[2:12] if customer.gstin else None,
            lglNm=customer.lglNm,  # Ensure this is not empty or missing
            addr1=customer.addr1,
            addr2=customer.addr2,
            addr3=customer.addr3,
            loc=customer.loc,
            pin=customer.pin,
            stcd=customer.stcd,
            defaultbalancegl=customer.defaultbalancegl,
            tdssection=customer.tdssection,
            tdsrate=customer.tdsrate,
            email=customer.email,
            mobile=customer.mobile,
            phone=customer.phone,
            bankac=customer.bankac,
            ifsc=customer.ifsc,
            is_trans_similar="Y"
        )
        session.execute(stmt)
        session.commit()

        # Fetch the newly created vendor
        new_customer = session.query(customer_table).filter(
            customer_table.c.abid == customer.abid,
            customer_table.c.gstin == customer.gstin
        ).first()
        new_customer_dict = {col.name: getattr(new_customer, col.name) for col in customer_table.columns}

        session.close()
        return {
            "status_code": 200,
            "message": "Customer created successfully.",
            "new_entry": new_customer_dict
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error creating customer: {e}")


class CustomerUpdate(BaseModel):
    abid: int
    gstin: Optional[str]  # Assuming gstin is used as a unique identifier
    customerid: int
    is_trans_similar: Optional[str] = None
    defaultbalancegl: Optional[int] = None
    lglNm: Optional[str] = None
    pan: Optional[str] = None
    tdssection: Optional[str] = None
    tdsrate: Optional[int] = None
    addr1: Optional[str] = None
    addr2: Optional[str] = None
    addr3: Optional[str] = None
    loc: Optional[str] = None
    pin: Optional[int] = None
    stcd: Optional[str] = None
    email: Optional[str] = None
    mobile: Optional[int] = None
    phone: Optional[int] = None
    bankac: Optional[str] = None
    ifsc: Optional[str] = None

@app.put("/update-customer")
async def update_customer(data: CustomerUpdate):
    try:
        # Open a database session
        session = SessionLocal()

        # Build the update query dynamically
        update_data = {
            key: (None if value == "" else value)  # Map empty strings to None
            for key, value in data.dict(exclude_unset=True).items()
            if key not in ["abid", "gstin"]
        }
        if not update_data:
            raise HTTPException(status_code=400, detail="No fields to update provided.")

        query = (
            update(customer_table)
            .where(customer_table.c.abid == data.abid, customer_table.c.customerid == data.customerid)
            .values(**update_data)
        )

        # Execute the query
        result = session.execute(query)
        session.commit()

        # Check if the record was updated
        if result.rowcount == 0:
            raise HTTPException(
                status_code=404,
                detail=f"Customer with abid={data.abid} and gstin={data.customerid} not found."
            )

        # Fetch the updated customer
        updated_customer = session.query(customer_table).filter(
            customer_table.c.abid == data.abid,
            customer_table.c.customerid == data.customerid
        ).first()
        updated_customer_dict = {col.name: getattr(updated_customer, col.name) for col in customer_table.columns}

        return {
            "status_code": 200,
            "message": "Customer updated successfully.",
            "updated_fields": update_data,
            "updated_entry": updated_customer_dict
        }

    except Exception as e:
        session.rollback()
        raise HTTPException(status_code=500, detail=f"Error updating customer: {str(e)}")

    finally:
        session.close()

#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#Journals put ^& post related API's

# Reflect the tables (using autoload_with=engine)
journal_lines_table = Table("journal_lines_v2", metadata, autoload_with=engine)
journal_header_table = Table("journal_header_v2", metadata, autoload_with=engine)
itemlist = Table("itemlist", metadata, autoload_with=engine)
invmaster = Table("invmaster", metadata, autoload_with=engine)
# Models
class ItemLine(BaseModel):
    SlNo: Optional[str] = None
    PrdDesc: Optional[str] = None
    IsServc: Optional[str] = None
    HsnCd: Optional[str] = None
    Barcde: Optional[str] = None
    Qty: Optional[float] = None  # Using float for DECIMAL type
    Unit: Optional[str] = None
    UnitPrice: Optional[float] = None  # Using float for DECIMAL type
    TotAmt: Optional[float] = None  # Using float for DECIMAL type
    Discount: Optional[float] = None  # Using float for DECIMAL type
    AssAmt: Optional[float] = None  # Using float for DECIMAL type
    GstRt: Optional[float] = None  # Using float for DECIMAL type
    IgstAmt: Optional[float] = None  # Using float for DECIMAL type
    CgstAmt: Optional[float] = None  # Using float for DECIMAL type
    SgstAmt: Optional[float] = None  # Using float for DECIMAL type
    CesRt: Optional[float] = None  # Using float for DECIMAL type
    CesAmt: Optional[float] = None  # Using float for DECIMAL type
    CesNonAdvlAmt: Optional[float] = None  # Using float for DECIMAL type
    StateCesRt: Optional[float] = None  # Using float for DECIMAL type
    StateCesAmt: Optional[float] = None  # Using float for DECIMAL type
    StateCesNonAdvlAmt: Optional[float] = None  # Using float for DECIMAL type
    OthChrg: Optional[float] = None  # Using float for DECIMAL type
    TotItemVal: Optional[float] = None  # Using float for DECIMAL type
    OrdLineRef: Optional[str] = None
    PrdSlNo: Optional[str] = None
    itemcode: Optional[int] = None  # Assuming BIGINT can be represented as int in Python
    item_id: Optional[int] = None  # Assuming BIGINT can be represented as int in Python
    gl_id: Optional[int] = None  # Assuming BIGINT can be represented as int in Python

class JournalLine(BaseModel):
    gl_id: int
    amount: float
    profit_center: Optional[int] = None
    cost_center: Optional[int] = None
    projectid: Optional[int] = None
    project_name: Optional[str] = None
    journalline_tag1: Optional[str] = None
    journalline_tag2: Optional[str] = None
    journalline_tag3: Optional[str] = None
    description: Optional[str] = None
    is_reversal_entry: Optional[str] = None

class JournalRequest(BaseModel):
    journal_description: Optional[str] = None  # For journal header
    abid: int
    transaction_type: Optional[str] = None
    source: Optional[str] = None
    cpty_gstin: Optional[str] = None
    cptyid: Optional[int] = None
    cptyname: Optional[str] = None
    ref_no: Optional[str] = None
    irn: Optional[str] = None
    pos: Optional[str] = None #newly added on 24th Jan
    doc_type: Optional[str] = None
    doc_date: Optional[date] = None
    posted_date: Optional[date] = None
    Status: Optional[str] = None
    version: Optional[str] = None
    assval: Optional[float] = None
    cgstval: Optional[float] = None
    sgstval: Optional[float] = None
    igstval: Optional[float] = None
    cessval: Optional[float] = None
    rndoffamt: Optional[float] = None
    othchrg: Optional[float] = None
    totinvval: Optional[float] = None
    assvalgl: Optional[int] = None
    cgstvalgl: Optional[int] = None
    sgstvalgl: Optional[int] = None
    igstvalgl: Optional[int] = None
    cessvalgl: Optional[int] = None
    rndoffamtgl: Optional[int] = None
    othchrggl: Optional[int] = None
    defaulttradegl: Optional[int] = None    
    defaultbalancegl: Optional[int] = None    
    is_itemize: Optional[str] = None
    is_input_availed: Optional[str] = None
    is_input_eligible_2b: Optional[str] = None
    is_cash_purchase: Optional[str] = None
    rtnprd_2b: Optional[str] = None
    is_rev_charge_2b_or_einv: Optional[str] = None
    pan: Optional[str] = None
    paymentref: Optional[str] = None
    payment_date : Optional[date] = None
    offsetgl:Optional[int] = None
    bankgl:Optional[int] = None
    narration:Optional[str] = None
    withdrawal: Optional[float] = None
    deposit: Optional[float] = None
    balance: Optional[float] = None
    lines: List[JournalLine]  # List of journal lines
    items: Optional[List[ItemLine]] = None  # Make items optional


# # FastAPI app
# app = FastAPI(debug=True)
    # 
   # assval: Optional[float] = None
    # cgstval: Optional[float] = None
    # sgstval: Optional[float] = None
    # igstval: Optional[float] = None
    # cessval: Optional[float] = None
    # rndoffamt: Optional[float] = None
    # othchrg: Optional[float] = None
    # totinvval: Optional[float] = None
    # is_itemize: Optional[str] = None
    # is_input_availed: Optional[str] = None
@app.post("/create_journal_einv")
async def create_journal_einv(entry: JournalRequest):
    """
    Create a new journal entry with multiple lines.
    """
    session = SessionLocal()

    try:

        # Reflect the table from the database

        query = session.query(journal_header_table).filter(
            journal_header_table.c.abid == entry.abid,
            journal_header_table.c.cpty_gstin == entry.cpty_gstin,
            journal_header_table.c.ref_no == entry.ref_no
            #journal_lines_table.c.version == entry.lines[1].version for now ignore version
        )
        existing_journal = query.first()


        if entry.Status == "ACT":
            # Insert a new journal header and get the journal_id
            if existing_journal:
                # Convert the existing journal to a dictionary
                existing_journal_dict = {col.name: getattr(existing_journal, col.name) for col in journal_header_table.columns}
                session.close()
                return {
                    "status_code": 403,
                    "message": "Document with the provided IRN already exists.",
                    "journal_id": existing_journal_dict["journal_id"]
                }
            else:
                header_data = {
                    "journal_description": entry.journal_description,
                    "abid": entry.abid,
                    "transaction_type": entry.transaction_type,
                    "source": entry.source,
                    "cpty_gstin": entry.cpty_gstin,
                    "cptyid": entry.cptyid,
                    "cptyname": entry.cptyname,
                    "ref_no": entry.ref_no,
                    "irn": entry.irn,
                    "pos": entry.pos,                 
                    "doc_type": entry.doc_type,
                    "doc_date": entry.doc_date,
                    "einv_Status": entry.Status,
                    "journal_status": entry.Status,
                    "version": entry.version,
                    "assvalgl": entry.assvalgl,
                    "cgstvalgl": entry.cgstvalgl,
                    "sgstvalgl": entry.sgstvalgl,
                    "igstvalgl": entry.igstvalgl,
                    "cessvalgl": entry.cessvalgl,
                    "rndoffamtgl": entry.rndoffamtgl,
                    "othchrggl": entry.othchrggl,
                    "defaulttradegl": entry.defaulttradegl,
                    "defaultbalancegl": entry.defaultbalancegl,
                    "assval": entry.assval,
                    "cgstval": entry.cgstval,
                    "sgstval": entry.sgstval,
                    "igstval": entry.igstval,
                    "cessval": entry.cessval,
                    "rndoffamt": entry.rndoffamt,
                    "othchrg": entry.othchrg,
                    "totinvval": entry.totinvval,
                    "is_itemize": entry.is_itemize,
                    "is_input_availed": entry.is_input_availed,
                    "is_rev_charge_2b_or_einv": entry.is_rev_charge_2b_or_einv,
                    "pan": entry.pan
                }

                stmt_header = insert(journal_header_table).values(**header_data)
                result = session.execute(stmt_header)

                journal_id = result.inserted_primary_key[0]  # Efficiently get the journal_id

                # Prepare all journal line data for batch insertion
                lines_data = [
                    {**line.model_dump(), "journal_id": journal_id, "abid": entry.abid,'is_reversal_entry':'N'} for line in entry.lines
                ]

                # Batch insert all journal lines
                if lines_data:
                    session.execute(journal_lines_table.insert(), lines_data)

                session.commit()

                return {"status_code": 200, "message": "Journal created successfully", "journal_id": journal_id}

        elif entry.Status == "CNL":
            # Handle cancellation logic
            if existing_journal:
                existing_journal_dict = {col.name: getattr(existing_journal, col.name) for col in journal_header_table.columns}
                if existing_journal_dict['einv_Status'] == "ACT" and existing_journal_dict['journal_status'] == "ACT":
                    
                    # Update einv_Status and journal_status to the new status
                    update_stmt = (
                        update(journal_header_table)
                        .where(journal_header_table.c.journal_id == existing_journal_dict['journal_id'])
                        .values(einv_Status=entry.Status, journal_status=entry.Status)
                    )
                    session.execute(update_stmt)

                    # Fetch existing journal lines to reverse them
                    lines_query = session.query(journal_lines_table).filter(
                        journal_lines_table.c.journal_id == existing_journal_dict['journal_id']
                    )
                    existing_lines = lines_query.all()

                    
                    
                    # Prepare reversal entries
                    reversal_lines_data = []
                    
                    for line in existing_lines:
                        reversal_amount = -line.amount  # Reverse the amount
                        reversal_line = {
                            'gl_id': line.gl_id,  # Keep the same GL ID
                            'amount': reversal_amount,
                            'profit_center': line.profit_center,  # Copy other attributes as needed
                            'cost_center': line.cost_center,
                            'projectid': line.projectid,
                            'project_name': line.project_name,
                            'journalline_tag1': line.journalline_tag1,
                            'journalline_tag2': line.journalline_tag2,
                            'journalline_tag3': line.journalline_tag3,
                            'description': line.description,
                            'is_reversal_entry': 'Y',  # Mark as reversal
                            'journal_id': line.journal_id,
                            'abid':line.abid
                        }
                        reversal_lines_data.append(reversal_line)

                    # Insert reversal entries into journal_lines_table
                    if reversal_lines_data:
                        session.execute(journal_lines_table.insert(), reversal_lines_data)

                    session.commit()

                    return {
                        "status_code": 200,
                        "message": "Invoice cancelled by vendor. Hence reversed.",
                        "journal_id": existing_journal_dict['journal_id']
                    }
                else:
                    return {
                    "status_code": 403,
                    "message": "Invoice is cancelled by vendor and looks like entry is also cancelled.",
                    "journal_id": existing_journal_dict["journal_id"]
                    }
            else:
                # Create a new journal with same payload received but add reversal lines
                header_data = {
                    "journal_description": entry.journal_description,
                    "abid": entry.abid,
                    "transaction_type": entry.transaction_type,
                    "source": entry.source,
                    "cpty_gstin": entry.cpty_gstin,
                    "cptyid": entry.cptyid,
                    "cptyname": entry.cptyname,
                    "ref_no": entry.ref_no,
                    "irn": entry.irn,  
                    "pos": entry.pos,               
                    "doc_type": entry.doc_type,
                    "doc_date": entry.doc_date,
                    "einv_Status": entry.Status,
                    "journal_status": entry.Status,
                    "version": entry.version,
                    "assval": entry.assval,
                    "cgstval": entry.cgstval,
                    "sgstval": entry.sgstval,
                    "igstval": entry.igstval,
                    "cessval": entry.cessval,
                    "rndoffamt": entry.rndoffamt,
                    "othchrg": entry.othchrg,
                    "totinvval": entry.totinvval,
                    "assvalgl": entry.assvalgl,
                    "cgstvalgl": entry.cgstvalgl,
                    "sgstvalgl": entry.sgstvalgl,
                    "igstvalgl": entry.igstvalgl,
                    "cessvalgl": entry.cessvalgl,
                    "rndoffamtgl": entry.rndoffamtgl,
                    "othchrggl": entry.othchrggl,
                    "defaulttradegl": entry.defaulttradegl,
                    "defaultbalancegl": entry.defaultbalancegl,
                    "is_itemize": entry.is_itemize,
                    "is_input_availed": entry.is_input_availed,
                    "is_rev_charge_2b_or_einv": entry.is_rev_charge_2b_or_einv,
                    "pan": entry.pan
                }

                stmt_header = insert(journal_header_table).values(**header_data)
                result = session.execute(stmt_header)

                journal_id = result.inserted_primary_key[0]

                # Prepare all journal line data for batch insertion
                lines_data = [
                    {**line.model_dump(), "journal_id": journal_id, "abid": entry.abid,'is_reversal_entry':"N"} for line in entry.lines
                ]

                # Prepare reversal entries
                reversal_lines_data = []
                
                for item in lines_data:
                    reversal_amount = -item['amount']  # Reverse the amount
                    reversal_line = {
                        'gl_id': item['gl_id'],  # Keep the same GL ID
                        'amount': reversal_amount,
                        'profit_center': item['profit_center'],  # Copy other attributes as needed
                        'cost_center': item['cost_center'],
                        'projectid': item['projectid'],
                        'project_name': item['project_name'],
                        'journalline_tag1': item['journalline_tag1'],
                        'journalline_tag2': item['journalline_tag2'],
                        'journalline_tag3': item['journalline_tag3'],
                        'description': item['description'],
                        'is_reversal_entry': 'Y',  # Mark as reversal
                        'journal_id': item['journal_id'],
                        'abid':item['abid']
                    }
                    reversal_lines_data.append(reversal_line)

                lines_data = lines_data+reversal_lines_data
                # Insert reversal entries into journal_lines_table
                if lines_data:
                    session.execute(journal_lines_table.insert(), lines_data)

                session.commit()
                
                return {
                   "status_code": 200, 
                   "message": "Journal created successfully for already cancelled entry just for disclosure.", 
                   "journal_id": journal_id
               }

    except Exception as e:
        session.rollback()  # Rollback in case of error
        import logging
        logging.basicConfig(level=logging.ERROR)
        logging.error(f"Error creating journal: {e}")
        raise HTTPException(status_code=500, detail=f"Error creating journal: {e}")

    finally:
        session.close()  # Ensure the session is closed
        

@app.post("/create_journal_gstr2b")
async def create_journal_gstr2b(entry: JournalRequest):
    """
    Create a new journal entry with multiple lines.
    """
    session = SessionLocal()

    try:

        # Reflect the table from the database

        query = session.query(journal_header_table).filter(
            journal_header_table.c.abid == entry.abid,
            journal_header_table.c.cpty_gstin == entry.cpty_gstin,
            journal_header_table.c.ref_no == entry.ref_no
            #journal_lines_table.c.version == entry.lines[1].version for now ignore version
        )
        existing_journal = query.first()


        # Insert a new journal header and get the journal_id
        if existing_journal:
            # Convert the existing journal to a dictionary
            existing_journal_dict = {col.name: getattr(existing_journal, col.name) for col in journal_header_table.columns}

            if existing_journal_dict["rtnprd_2b"] == None or existing_journal_dict["rtnprd_2b"] == "":
                header_data = {
                    # "is_input_availed": entry.is_input_availed,#if previously availed and now if you are changing this then journal entry must also change. Leave this for now
                    "is_input_eligible_2b": entry.is_input_eligible_2b,
                    "rtnprd_2b": entry.rtnprd_2b
                }
                # Create an update statement
                stmt = update(journal_header_table).where(
                    journal_header_table.c.abid == entry.abid,
                    journal_header_table.c.journal_id ==existing_journal_dict["journal_id"] ).values(**header_data)

                # Execute the update statement
                result = session.execute(stmt)
                session.commit()  # Commit the transaction

                return {
                    "status_code": 200,
                    "message": "Updated 2b data in journal. Document with the ref & gstin already exist.",
                    "journal_id": existing_journal_dict["journal_id"]
                }
            else:    
                session.close()
                return {
                    "status_code": 403,
                    "message": "Document with the ref & gstin already exist and 2b data is also already updated.",
                    "journal_id": existing_journal_dict["journal_id"]
                }
        else:
            header_data = {
                "journal_description": entry.journal_description,
                "abid": entry.abid,
                "transaction_type": entry.transaction_type,
                "source":entry.source,
                "cpty_gstin": entry.cpty_gstin,
                "cptyid": entry.cptyid,
                "cptyname": entry.cptyname,
                "ref_no": entry.ref_no,
                "irn": entry.irn,
                "pos": entry.pos,                
                "doc_type": entry.doc_type,
                "doc_date": entry.doc_date,
                "journal_status": "ACT",
                "version": entry.version,
                "assval": entry.assval,
                "cgstval": entry.cgstval,
                "sgstval": entry.sgstval,
                "igstval": entry.igstval,
                "cessval": entry.cessval,
                "rndoffamt": entry.rndoffamt,
                "othchrg": entry.othchrg,
                "totinvval": entry.totinvval,
                "assvalgl": entry.assvalgl,
                "cgstvalgl": entry.cgstvalgl,
                "sgstvalgl": entry.sgstvalgl,
                "igstvalgl": entry.igstvalgl,
                "cessvalgl": entry.cessvalgl,
                "rndoffamtgl": entry.rndoffamtgl,
                "othchrggl": entry.othchrggl,
                "defaulttradegl": entry.defaulttradegl,
                "defaultbalancegl": entry.defaultbalancegl,
                "is_itemize": entry.is_itemize,
                "is_input_availed": entry.is_input_availed,
                "is_input_eligible_2b": entry.is_input_eligible_2b,
                "rtnprd_2b": entry.rtnprd_2b,
                "is_rev_charge_2b_or_einv":entry.is_rev_charge_2b_or_einv,
                "pan": entry.pan
            }

                # "source":entry.source,
                # "assval": entry.assval,
                # "cgstval": entry.cgstval,
                # "sgstval": entry.sgstval,
                # "igstval": entry.igstval,
                # "cessval": entry.cessval,
                # "rndoffamt": entry.rndoffamt,
                # "othchrg": entry.othchrg,
                # "totinvval": entry.totinvval,
                # "is_itemize": entry.is_itemize,
                # "is_input_availed": entry.is_input_availed,
            stmt_header = insert(journal_header_table).values(**header_data)
            result = session.execute(stmt_header)

            journal_id = result.inserted_primary_key[0]  # Efficiently get the journal_id

            # Prepare all journal line data for batch insertion
            lines_data = [
                {**line.model_dump(), "journal_id": journal_id, "abid": entry.abid} for line in entry.lines
            ]

            # Batch insert all journal lines
            if lines_data:
                session.execute(journal_lines_table.insert(), lines_data)

            session.commit()

            return {"status_code": 200,"message": "Journal created successfully", "journal_id": journal_id}
        
        # session.commit()


    except Exception as e:
        session.rollback()  # Rollback in case of error
        import logging
        logging.basicConfig(level=logging.ERROR)
        logging.error(f"Error creating journal: {e}")
        raise HTTPException(status_code=500, detail=f"Error creating journal: {e}")

    finally:
        session.close()  # Ensure the session is closed
        
@app.post("/create_journal_manual")
async def create_journal_manual(entry: JournalRequest):
    """
    Create a new journal entry with multiple lines.
    """
    session = SessionLocal()

    try:

        # Reflect the table from the database

        query = session.query(journal_header_table).filter(
            journal_header_table.c.abid == entry.abid,
            journal_header_table.c.cpty_gstin == entry.cpty_gstin,
            journal_header_table.c.ref_no == entry.ref_no
            #journal_lines_table.c.version == entry.lines[1].version for now ignore version
        )
        existing_journal = query.first()


        # Insert a new journal header and get the journal_id
        if existing_journal:
            # Convert the existing journal to a dictionary
            existing_journal_dict = {col.name: getattr(existing_journal, col.name) for col in journal_header_table.columns}

            session.close()
            return {
                "status_code": 403,
                "message": "Document with the ref & gstin already exist.",
                "journal_id": existing_journal_dict["journal_id"]
            }
        else:
            header_data = {
                "journal_description": entry.journal_description,
                "abid": entry.abid,
                "transaction_type": entry.transaction_type,
                "source":entry.source,
                "cpty_gstin": entry.cpty_gstin,
                "cptyid": entry.cptyid,
                "cptyname": entry.cptyname,
                "ref_no": entry.ref_no,
                "irn": entry.irn,   
                "pos": entry.pos,               
                "doc_type": entry.doc_type,
                "doc_date": entry.doc_date,
                "posted_date": entry.doc_date,
                "journal_status": "ACT",
                "version": entry.version,
                
                "assvalgl": entry.assvalgl,
                "cgstvalgl": entry.cgstvalgl,
                "sgstvalgl": entry.sgstvalgl,
                "igstvalgl": entry.igstvalgl,
                "cessvalgl": entry.cessvalgl,
                "rndoffamtgl": entry.rndoffamtgl,
                "othchrggl": entry.othchrggl,
                "defaulttradegl": entry.defaulttradegl,
                "defaultbalancegl": entry.defaultbalancegl,

                "assval": entry.assval,
                "cgstval": entry.cgstval,
                "sgstval": entry.sgstval,
                "igstval": entry.igstval,
                "cessval": entry.cessval,
                "rndoffamt": entry.rndoffamt,
                "othchrg": entry.othchrg,
                "totinvval": entry.totinvval,
                "is_itemize": entry.is_itemize,
                "is_input_availed": entry.is_input_availed,
                "is_input_eligible_2b": entry.is_input_eligible_2b,
                "rtnprd_2b": entry.rtnprd_2b,
                "is_rev_charge_2b_or_einv":entry.is_rev_charge_2b_or_einv,
                "pan": entry.pan
            }

                # "source":entry.source,
                # "assval": entry.assval,
                # "cgstval": entry.cgstval,
                # "sgstval": entry.sgstval,
                # "igstval": entry.igstval,
                # "cessval": entry.cessval,
                # "rndoffamt": entry.rndoffamt,
                # "othchrg": entry.othchrg,
                # "totinvval": entry.totinvval,
                # "is_itemize": entry.is_itemize,
                # "is_input_availed": entry.is_input_availed,
            stmt_header = insert(journal_header_table).values(**header_data)
            result = session.execute(stmt_header)

            journal_id = result.inserted_primary_key[0]  # Efficiently get the journal_id

            # Prepare all journal line data for batch insertion
            lines_data = [
                {**line.model_dump(), "journal_id": journal_id, "abid": entry.abid} for line in entry.lines
            ]

            if entry.is_itemize == "Y":
                items_data = [
                    {**item.model_dump(), "journal_id": journal_id, "abid": entry.abid} for item in entry.items
                ]            
                # Batch insert all items
                if items_data:
                    session.execute(itemlist.insert(), items_data)


            # Batch insert all journal lines
            if lines_data:
                session.execute(journal_lines_table.insert(), lines_data)



            session.commit()

            return {"status_code": 200,"message": "Journal created successfully", "journal_id": journal_id}
        
        # session.commit()


    except Exception as e:
        session.rollback()  # Rollback in case of error
        import logging
        logging.basicConfig(level=logging.ERROR)
        logging.error(f"Error creating journal: {e}")
        raise HTTPException(status_code=500, detail=f"Error creating journal: {e}")

    finally:
        session.close()  # Ensure the session is closed

      
@app.post("/create_journal_bankupload")
async def create_journal_bankupload(entry: JournalRequest):
    """
    Create a new journal entry for bank transactions with multiple lines.
    """
    session = SessionLocal()

    try:

        # Reflect the table from the database

        query = session.query(journal_header_table).filter(
            journal_header_table.c.abid == entry.abid,
            journal_header_table.c.paymentref == entry.paymentref,
            journal_header_table.c.payment_date == entry.payment_date,
            journal_header_table.c.narration == entry.narration
            #journal_lines_table.c.version == entry.lines[1].version for now ignore version
        )
        existing_journal = query.first()


        # Insert a new journal header and get the journal_id
        if existing_journal:
            # Convert the existing journal to a dictionary
            existing_journal_dict = {col.name: getattr(existing_journal, col.name) for col in journal_header_table.columns}

            session.close()
            return {
                "status_code": 403,
                "message": "Payment already recorded.",
                "journal_id": existing_journal_dict["journal_id"]
            }
        else:
            header_data = {
                "journal_description": entry.journal_description,
                "posted_date": entry.posted_date,
                "abid": entry.abid,
                "transaction_type": entry.transaction_type,
                "source":entry.source,
                "cpty_gstin": entry.cpty_gstin,
                "cptyid": entry.cptyid,
                "cptyname": entry.cptyname,
                "journal_status": "ACT",
                "pan": entry.pan,
                "bankgl":entry.bankgl,
                "offsetgl":entry.offsetgl,
                "payment_date": entry.payment_date,
                "paymentref": entry.paymentref,
                "narration":entry.narration,
                "withdrawal":entry.withdrawal,
                "deposit":entry.deposit,
                "balance":entry.balance,
            }

                # "source":entry.source,
                # "assval": entry.assval,
                # "cgstval": entry.cgstval,
                # "sgstval": entry.sgstval,
                # "igstval": entry.igstval,
                # "cessval": entry.cessval,
                # "rndoffamt": entry.rndoffamt,
                # "othchrg": entry.othchrg,
                # "totinvval": entry.totinvval,
                # "is_itemize": entry.is_itemize,
                # "is_input_availed": entry.is_input_availed,
            stmt_header = insert(journal_header_table).values(**header_data)
            result = session.execute(stmt_header)

            journal_id = result.inserted_primary_key[0]  # Efficiently get the journal_id

            # Prepare all journal line data for batch insertion
            lines_data = [
                {**line.model_dump(), "journal_id": journal_id, "abid": entry.abid} for line in entry.lines
            ]

            # if entry.is_itemize == "Y":
            #     items_data = [
            #         {**item.model_dump(), "journal_id": journal_id, "abid": entry.abid} for item in entry.items
            #     ]            
            #     # Batch insert all items
            #     if items_data:
            #         session.execute(itemlist.insert(), items_data)


            # Batch insert all journal lines
            if lines_data:
                session.execute(journal_lines_table.insert(), lines_data)



            session.commit()

            return {"status_code": 200,"message": "Journal created successfully", "journal_id": journal_id}
        
        # session.commit()


    except Exception as e:
        session.rollback()  # Rollback in case of error
        import logging
        logging.basicConfig(level=logging.ERROR)
        logging.error(f"Error creating journal: {e}")
        raise HTTPException(status_code=500, detail=f"Error creating journal: {e}")

    finally:
        session.close()  # Ensure the session is closed

      
@app.post("/create_journal_bankuploadbulk")
async def create_journal_bankuploadbulk(entries: List[JournalRequest]):
    """
    Create a new journal entry for bank transactions with multiple lines.
    """
    session = SessionLocal()
    response_list = []
    for entry in entries:
        try:

            # Reflect the table from the database

            query = session.query(journal_header_table).filter(
                journal_header_table.c.abid == entry.abid,
                journal_header_table.c.paymentref == entry.paymentref,
                journal_header_table.c.payment_date == entry.payment_date,
                journal_header_table.c.narration == entry.narration
                #journal_lines_table.c.version == entry.lines[1].version for now ignore version
            )
            existing_journal = query.first()


            # Insert a new journal header and get the journal_id
            if existing_journal:
                # Convert the existing journal to a dictionary
                existing_journal_dict = {col.name: getattr(existing_journal, col.name) for col in journal_header_table.columns}

                session.close()
                response_list.append({
                    "status_code": 403,
                    "message": "Payment already recorded.",
                    "journal_id": existing_journal_dict["journal_id"]
                })
                continue  # Move to the next entry instead of stopping execution
            else:
                header_data = {
                    "journal_description": entry.journal_description,
                    "posted_date": entry.posted_date,
                    "abid": entry.abid,
                    "transaction_type": entry.transaction_type,
                    "source":entry.source,
                    "cpty_gstin": entry.cpty_gstin,
                    "cptyid": entry.cptyid,
                    "cptyname": entry.cptyname,
                    "journal_status": "ACT",
                    "pan": entry.pan,
                    "bankgl":entry.bankgl,
                    "offsetgl":entry.offsetgl,
                    "payment_date": entry.payment_date,
                    "paymentref": entry.paymentref,
                    "narration":entry.narration,
                    "withdrawal":entry.withdrawal,
                    "deposit":entry.deposit,
                    "balance":entry.balance,
                }


                stmt_header = insert(journal_header_table).values(**header_data)
                result = session.execute(stmt_header)

                journal_id = result.inserted_primary_key[0]  # Efficiently get the journal_id

                # Prepare all journal line data for batch insertion
                lines_data = [
                    {**line.model_dump(), "journal_id": journal_id, "abid": entry.abid} for line in entry.lines
                ]

                # if entry.is_itemize == "Y":
                #     items_data = [
                #         {**item.model_dump(), "journal_id": journal_id, "abid": entry.abid} for item in entry.items
                #     ]            
                #     # Batch insert all items
                #     if items_data:
                #         session.execute(itemlist.insert(), items_data)


                # Batch insert all journal lines
                if lines_data:
                    session.execute(journal_lines_table.insert(), lines_data)



                session.commit()
                response_list.append({"status_code": 200, "message": "Journal created successfully", "journal_id": journal_id})
                # response_list.append(response)
                # return {"status_code": 200,"message": "Journal created successfully", "journal_id": journal_id}
            
            # session.commit()


        except Exception as e:
            session.rollback()  # Rollback only for the failed entry
            logging.error(f"Error creating journal for {entry.paymentref}: {e}")
            response_list.append({
                "status_code": 500,
                "message": f"Error creating journal for {entry.paymentref}: {e}",
                "journal_id": None
            })

    session.close()  # Close session after processing all entries
    return response_list  # Return all responses, success and failures      

# Models
class EditItemLine(BaseModel):

    SlNo: Optional[str] = None
    PrdDesc: Optional[str] = None
    IsServc: Optional[str] = None
    HsnCd: Optional[str] = None
    Barcde: Optional[str] = None
    Qty: Optional[float] = None  # Using float for DECIMAL type
    Unit: Optional[str] = None
    UnitPrice: Optional[float] = None  # Using float for DECIMAL type
    TotAmt: Optional[float] = None  # Using float for DECIMAL type
    Discount: Optional[float] = None  # Using float for DECIMAL type
    AssAmt: Optional[float] = None  # Using float for DECIMAL type
    GstRt: Optional[float] = None  # Using float for DECIMAL type
    IgstAmt: Optional[float] = None  # Using float for DECIMAL type
    CgstAmt: Optional[float] = None  # Using float for DECIMAL type
    SgstAmt: Optional[float] = None  # Using float for DECIMAL type
    CesRt: Optional[float] = None  # Using float for DECIMAL type
    CesAmt: Optional[float] = None  # Using float for DECIMAL type
    CesNonAdvlAmt: Optional[float] = None  # Using float for DECIMAL type
    StateCesRt: Optional[float] = None  # Using float for DECIMAL type
    StateCesAmt: Optional[float] = None  # Using float for DECIMAL type
    StateCesNonAdvlAmt: Optional[float] = None  # Using float for DECIMAL type
    OthChrg: Optional[float] = None  # Using float for DECIMAL type
    TotItemVal: Optional[float] = None  # Using float for DECIMAL type
    OrdLineRef: Optional[str] = None
    PrdSlNo: Optional[str] = None
    itemcode: Optional[int] = None  # Assuming BIGINT can be represented as int in Python
    item_id: Optional[int] = None  # Assuming BIGINT can be represented as int in Python
    gl_id: Optional[int] = None  # Assuming BIGINT can be represented as int in Python

class EditJournalLine(BaseModel):
    # journal_id:int
    # abid:int
    # line_id:int
    gl_id: int
    amount: float
    profit_center: Optional[int] = None
    cost_center: Optional[int] = None
    projectid: Optional[int] = None
    project_name: Optional[str] = None
    journalline_tag1: Optional[str] = None
    journalline_tag2: Optional[str] = None
    journalline_tag3: Optional[str] = None
    description: Optional[str] = None
    is_reversal_entry: Optional[str] = None

class EditJournalRequest(BaseModel):
    journal_id: int # For journal header
    journal_description: Optional[str] = None  # For journal header
    abid: int
    transaction_type: Optional[str] = None
    source: Optional[str] = None
    cpty_gstin: Optional[str] = None
    cptyid: Optional[int] = None
    cptyname: Optional[str] = None
    ref_no: Optional[str] = None
    irn: Optional[str] = None
    pos: Optional[str] = None #newly added on 24th Jan
    doc_type: Optional[str] = None
    doc_date: Optional[date] = None
    posted_date: Optional[date] = None
    version: Optional[str] = None
    assval: Optional[float] = None
    cgstval: Optional[float] = None
    sgstval: Optional[float] = None
    igstval: Optional[float] = None
    cessval: Optional[float] = None
    rndoffamt: Optional[float] = None
    othchrg: Optional[float] = None
    totinvval: Optional[float] = None
    assvalgl: Optional[int] = None
    cgstvalgl: Optional[int] = None
    sgstvalgl: Optional[int] = None
    igstvalgl: Optional[int] = None
    cessvalgl: Optional[int] = None
    rndoffamtgl: Optional[int] = None
    othchrggl: Optional[int] = None
    defaulttradegl: Optional[int] = None    
    defaultbalancegl: Optional[int] = None    
    is_itemize: Optional[str] = None
    is_input_availed: Optional[str] = None
    is_input_eligible_2b: Optional[str] = None
    is_cash_purchase: Optional[str] = None
    rtnprd_2b: Optional[str] = None
    is_rev_charge_2b_or_einv: Optional[str] = None
    pan: Optional[str] = None
    paymentref: Optional[str] = None
    payment_date : Optional[date] = None
    offsetgl:Optional[int] = None
    bankgl:Optional[int] = None
    narration:Optional[str] = None
    withdrawal: Optional[float] = None
    deposit: Optional[float] = None
    balance: Optional[float] = None
    lines: List[EditJournalLine]  # List of journal lines
    items: Optional[List[EditItemLine]] = None  # Make items optional


    
@app.put("/edit_journal_bank")
async def update_journal_bank(entry: EditJournalRequest):
    session = SessionLocal()
    try:
        # Check if the journal exists
        existing_journal = session.query(journal_header_table).filter(
            journal_header_table.c.journal_id == entry.journal_id,
            journal_header_table.c.abid == entry.abid
        ).first()

        if not existing_journal:
            return {"status_code": 404, "message": "Journal not found"}

        # Update journal header
        session.query(journal_header_table).filter(
            journal_header_table.c.journal_id == entry.journal_id
        ).update(entry.dict(exclude={"lines"}))

        # **Delete all existing journal lines**
        session.execute(delete(journal_lines_table).where(journal_lines_table.c.journal_id == entry.journal_id))

        # **Insert new journal lines**
        if entry.lines:
            new_lines_data = [
                {**line.model_dump(), "journal_id": entry.journal_id, "abid": entry.abid}
                for line in entry.lines
            ]
            session.execute(journal_lines_table.insert(), new_lines_data)

        session.commit()
        return {"status_code": 200, "message": "Journal updated successfully"}

    except Exception as e:
        session.rollback()
        return {"status_code": 500, "message": f"Error: {str(e)}"}
    finally:
        session.close()

    
@app.put("/edit_bill")
async def edit_bill(entry: EditJournalRequest):
    session = SessionLocal()
    try:
        # Check if the journal exists
        existing_journal = session.query(journal_header_table).filter(
            journal_header_table.c.journal_id == entry.journal_id,
            journal_header_table.c.abid == entry.abid
        ).first()

        if not existing_journal:
            return {"status_code": 404, "message": "Journal not found"}

        # Update journal header
        session.query(journal_header_table).filter(
            journal_header_table.c.journal_id == entry.journal_id
        ).update(entry.model_dump(exclude={"lines", "items"}))


        # **Delete all existing journal lines**
        session.execute(delete(journal_lines_table).where(journal_lines_table.c.journal_id == entry.journal_id))

        if existing_journal.is_itemize == 'Y':
            session.execute(delete(itemlist).where(itemlist.c.journal_id == entry.journal_id))        

        # **Insert new journal lines**
        if entry.lines:
            new_lines_data = [
                {**line.model_dump(), "journal_id": entry.journal_id, "abid": entry.abid}
                for line in entry.lines
            ]
            session.execute(journal_lines_table.insert(), new_lines_data)

        if entry.is_itemize == "Y" and entry.items:
            items_data = [
                {**item.model_dump(), "journal_id": entry.journal_id, "abid": entry.abid} for item in entry.items
            ]            
            # Batch insert all items
            if items_data:
                session.execute(itemlist.insert(), items_data)            

        session.commit()
        return {"status_code": 200, "message": "Bill updated successfully"}

    except Exception as e:
        session.rollback()
        return {"status_code": 500, "message": f"Error: {str(e)}"}
    finally:
        session.close()

class GLamendinJournal(BaseModel):
    abid: int
    old_gl_id: int  # Original GL ID to match
    vendorid: int  # Vendor ID for filtering
    new_gl_id: int  # New GL ID to set

@app.put("/update_gl_in_journal")
async def update_gl_in_journal(entry: GLamendinJournal):
    """
    Update gl_id for all journal lines matching given abid, old_gl_id, and vendorid.
    """
    session = SessionLocal()

    try:
        # Subquery to find relevant journal_ids by vendorid
        subquery = (
            select(journal_header_table.c.journal_id)
            .filter(journal_header_table.c.cptyid == entry.vendorid)
        )

        # Prepare update statement for changing gl_id to new_gl_id
        stmt = (
            update(journal_lines_table)
            .where(
                journal_lines_table.c.abid == entry.abid,
                journal_lines_table.c.gl_id == entry.old_gl_id,
                journal_lines_table.c.journal_id.in_(subquery)  # Ensure journal_id matches those from the vendor
            )
            .values(gl_id=entry.new_gl_id)  # Update gl_id to new_gl_id
        )

        result = session.execute(stmt)  # Execute the update statement

        session.commit()  # Commit changes
        
        # Get the number of updated records
        updated_count = result.rowcount

        if updated_count == 0:
            return {
                "status_code": 404,
                "message": "No matching records found.",
                "updated_records": 0  # Include number of updated
            }

        return {
            "status_code": 200,
            "message": f"GL ID updated successfully for {updated_count} records",
            "updated_records": updated_count  # Include number of updated
        }

    except HTTPException as http_ex:
        raise http_ex  # Re-raise HTTP exceptions to maintain status codes

    except Exception as e:
        session.rollback()  # Rollback in case of error
        logging.basicConfig(level=logging.ERROR)
        logging.error(f"Error updating GL ID: {e}")
        
        raise HTTPException(status_code=500, detail=f"Error updating GL ID: {e}")

    finally:
        session.close()  # Ensure the session is closed

#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#Journals get related API's


@app.get("/journal_header")
async def get_journal_header_by_abid(
    abid: int,
    journal_id:int

    ):
    """
    Retrieves journal lines for a given abid with pagination, filtering, and sorting.
    """
    try:


        # Open database session
        session = SessionLocal()

        # Apply both filters in one go using `and_`
        query = select(journal_header_table).where(
                journal_header_table.c.abid == abid,
                journal_header_table.c.journal_id == journal_id
            )
        

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()  # Ensure session is closed
            raise HTTPException(status_code=404, detail=f"No journal_header found for abid={abid}")

        # Convert the result to a list of dictionaries
        header_response = [
            {column.name: value for column, value in zip(journal_header_table.columns, row)}
            for row in result
        ]
        
        # session.close()
        # return header_response

        query_lines = select(journal_lines_table).where(journal_lines_table.c.journal_id == journal_id)
        result_lines = session.execute(query_lines)
        lines_data = result_lines.fetchall()
        
        # Convert the result to a list of dictionaries
        line_response = [
            {column.name: value for column, value in zip(journal_lines_table.columns, row)}
            for row in lines_data
        ]

        header_response[0]["lines"]=line_response

        if header_response[0]['is_itemize']=='Y':
            query_items = select(itemlist).where(itemlist.c.journal_id == journal_id)
            result_items = session.execute(query_items)
            items_data = result_items.fetchall()
            
            # Convert the result to a list of dictionaries
            item_response = [
                {column.name: value for column, value in zip(itemlist.columns, row)}
                for row in items_data
            ]
            header_response[0]["items"]=item_response

        response = header_response[0]
        session.close()
        return response

    except HTTPException as e:
        raise e  # Explicitly re-raise HTTP exceptions like 404

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error fetching journal data: {str(e)}")


@app.get("/journal_header_list_bill")
async def get_journal_header_by_abid(
    abid: int

):
    """
    Retrieves journal lines for a given abid with pagination, filtering, and sorting.
    """
    try:


        # Open database session
        session = SessionLocal()

        # Apply both filters in one go using `and_`
        query = select(journal_header_table).where(
                journal_header_table.c.abid == abid,
                journal_header_table.c.transaction_type == "bill"
            )
        

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()  # Ensure session is closed
            raise HTTPException(status_code=404, detail=f"No journal_header found for abid={abid}")

        # Convert the result to a list of dictionaries
        header_response = [
            {column.name: value for column, value in zip(journal_header_table.columns, row)}
            for row in result
        ]
        
        # session.close()
        # return header_response

        # # Prepare a list to hold all journal lines for each header
        # for header in header_response:
        #     journal_id = header['journal_id']  # Get the journal_id from the header
            
        #     # Query to fetch corresponding lines for each journal_id
        #     query_lines = select(journal_lines_table).where(
        #         journal_lines_table.c.journal_id == journal_id
        #     )
            
        #     # Execute the query for lines
        #     result_lines = session.execute(query_lines).fetchall()
            
        #     # Convert the result to a list of dictionaries for lines
        #     line_response = [
        #         {column.name: value for column, value in zip(journal_lines_table.columns, row)}
        #         for row in result_lines
        #     ]
            
        #     # Add the lines to the corresponding header response
        #     header["journal_lines"] = line_response

        session.close()
        return header_response  # Return all headers with their corresponding lines

    except HTTPException as e:
        raise e  # Explicitly re-raise HTTP exceptions like 404

    except Exception as e:
        session.rollback()  # Rollback in case of error
        logging.basicConfig(level=logging.ERROR)
        logging.error(f"Error fetching journal data: {str(e)}")
        
        raise HTTPException(status_code=500, detail=f"Error fetching journal data: {str(e)}")


@app.get("/journal_header_list_payment_receipt")
async def get_journal_header_by_abid(
    abid: int,
    sort_by: Optional[str] = Query("payment_date", enum=["payment_date", "other_column_1", "other_column_2"]),  # Add other columns as needed
    sort_order: Optional[str] = Query("desc", enum=["asc", "desc"]),
    page: Optional[int] = Query(1, ge=1),  # Page number, must be >= 1
    page_size: Optional[int] = Query(50, ge=1),  # Page size, must be >= 1
    filter: Optional[str] = None  # Filter parameter (if needed)
    ):
    """
    Retrieves journal lines for a given abid with pagination, filtering, and sorting.
    """
    try:


        # Open database session
        session = SessionLocal()

        # Apply both filters in one go using `and_`
        query = select(journal_header_table).where(
                journal_header_table.c.abid == abid,
                journal_header_table.c.transaction_type.in_(["payment", "receipt"])
            )

        # Apply filter if provided (assuming filtering by a specific column)
        if filter:
            query = query.where(journal_header_table.c.offsetgl.ilike(f"%{filter}%"))  # Adjust 'some_column' as per your requirement

        # Count total records for pagination
        total_count_query = select(func.count()).where(
            journal_header_table.c.abid == abid,
            journal_header_table.c.transaction_type.in_(["payment", "receipt"])
        )
        
        if filter:
            total_count_query = total_count_query.where(journal_header_table.c.offsetgl.ilike(f"%{filter}%"))

        total_count_result = session.execute(total_count_query).scalar()  # Get total count of records

       # Apply sorting based on parameters
        if sort_order == "asc":
            query = query.order_by(getattr(journal_header_table.c, sort_by).asc())
        else:
            query = query.order_by(getattr(journal_header_table.c, sort_by).desc())

        # Pagination logic
        offset = (page - 1) * page_size  # Calculate offset for pagination
        query = query.offset(offset).limit(page_size)  # Apply pagination

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()  # Ensure session is closed
            raise HTTPException(status_code=404, detail=f"No journal_header found for abid={abid}")

        # Convert the result to a list of dictionaries
        header_response = [
            {column.name: value for column, value in zip(journal_header_table.columns, row)}
            for row in result
        ]
        
        # Calculate total pages based on total count and page size
        total_pages = (total_count_result + page_size - 1) // page_size  # Ceiling division for total pages


        session.close()
        return {
            "headers": header_response,
            "total_count": total_count_result,
            "total_pages": total_pages,
            "current_page": page,
            "page_size": page_size,
        }  # Return all headers with their corresponding lines

    except HTTPException as e:
        raise e  # Explicitly re-raise HTTP exceptions like 404

    except Exception as e:
        session.rollback()  # Rollback in case of error
        logging.basicConfig(level=logging.ERROR)
        logging.error(f"Error fetching journal data: {str(e)}")
        
        raise HTTPException(status_code=500, detail=f"Error fetching journal data: {str(e)}")


#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------

invmaster_v2_table = Table("invmaster_v2", metadata, autoload_with=engine)
invmaster_header_table = Table("invmaster_header", metadata, autoload_with=engine)

@app.get("/invmaster")
async def get_invmaster_by_abid(abid: int):
    """
    Retrieves all items from invmaster_v2 for a given abid.
    """
    try:
        # Open database session
        session = SessionLocal()

        # Build the query
        query = select(invmaster_v2_table).where(invmaster_v2_table.c.abid == abid)

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()
            return {"status_code": 404, "message": "No records found."}
            raise HTTPException(
                status_code=404,
                detail=f"No records found for abid={abid}"
            )

        # Convert the result to a list of dictionaries
        response = [
            {column.name: value for column, value in zip(invmaster_v2_table.columns, row)}
            for row in result
        ]
        session.close()
        return {"status_code": 200, "message": "Success.", "response": response}

    except HTTPException as e:
        raise e

    except Exception as e:
        logging.error(f"Error fetching invmaster data: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error fetching invmaster data: {str(e)}")


# Assuming InvmasterItemV2 is defined with the correct fields for invmaster_v2
class InvmasterItemV2(BaseModel):
    abid: Optional[int] = None
    PrdDesc: Optional[str] = None
    HsnCd: Optional[str] = None
    Barcde: Optional[str] = None
    IsServc: Optional[str] = None
    Unit: Optional[str] = None
    UnitPrice: Optional[float] = None
    GstRt: Optional[float] = None
    CesRt: Optional[float] = None
    CesNonAdvlAmt: Optional[float] = None
    StateCesRt: Optional[float] = None
    StateCesNonAdvlAmt: Optional[float] = None
    is_PrdSlNo: Optional[str] = None
    itemcode: Optional[int] = None
    item_id: Optional[int] = None
    inv_gl_id: Optional[int] = None
    exp_gl_id: Optional[int] = None
    inc_gl_id: Optional[int] = None
    margin_percent: Optional[float] = None
    selling_price: Optional[float] = None
    created_at: Optional[str] = None
    approved_by: Optional[str] = None
    created_by: Optional[str] = None
    manufacturer: Optional[str] = None
    vendor_id: Optional[int] = None
    vendor_unit: Optional[str] = None
    vendor_unitprice: Optional[float] = None
    unit_conversion_factor: Optional[float] = None
    vendor_PrdDesc: Optional[str] = None
    status: Optional[str] = None
    MRP: Optional[float] = None
    BchDtls_Nm: Optional[str] = None
    BchDtls_ExpDt: Optional[str] = None
    BchDtls_WrDt: Optional[str] = None
    item_group_id: Optional[int] = None


class BarcodeRequest(BaseModel):
    abid: int
    Barcde: str

invmaster_v2_table = Table("invmaster_v2", metadata, autoload_with=engine)

@app.post("/invmaster_v2_item_barcode")
async def get_invmaster_v2_item_barcode(
    request_data: BarcodeRequest
):
    """
    Fetch items from invmaster_v2 with matching abid and Barcde.
    """
    try:
        # Open database session
        session = SessionLocal()

        # Build the query
        query = select(invmaster_v2_table).where(
            invmaster_v2_table.c.abid == request_data.abid,
            invmaster_v2_table.c.Barcde == request_data.Barcde
        )

        # Execute the query
        result = session.execute(query).fetchall()

        # Handle case when no records are found
        if not result:
            session.close()
            return {"status_code": 404, "message": "No records found."}

        # Convert the result to a list of dictionaries
        response = [
            {column.name: value for column, value in zip(invmaster_v2_table.columns, row)}
            for row in result
        ]
        session.close()
        return {"status_code": 200, "message": "Success.", "response": response}

    except HTTPException as e:
        raise e

    except Exception as e:
        logging.error(f"Error fetching invmaster_v2 data: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error fetching invmaster_v2 data: {str(e)}")

        
# Data Models (Pydantic)
class InvMasterRequest(BaseModel):
    abid: int
    PrdDesc: str
    HsnCd: Optional[str] = None
    Barcde: Optional[str] = None
    Qty: Optional[float] = Field(default=0.0)
    Unit: Optional[str] = None
    UnitPrice: Optional[float] = Field(default=0.0)
    GstRt: Optional[float] = Field(default=0.0)
    CesRt: Optional[float] = Field(default=0.0)
    CesNonAdvlAmt: Optional[float] = Field(default=0.0)
    StateCesRt: Optional[float] = Field(default=0.0)
    StateCesNonAdvlAmt: Optional[float] = Field(default=0.0)
    is_PrdSlNo: Optional[str] = None
    IsServc: Optional[str] = None
    itemcode: Optional[int] = None
    inv_gl_id: Optional[int] = None
    exp_gl_id: Optional[int] = None
    inc_gl_id: Optional[int] = None
    margin_percent: Optional[float] = Field(default=0.0)
    selling_price: Optional[float] = Field(default=0.0)
    manufacturer: Optional[str] = None
    vendor_id: Optional[int] = None
    vendor_unit: Optional[str] = None
    vendor_unitprice: Optional[float] = None
    unit_conversion_factor: Optional[float] = None
    vendor_PrdDesc: Optional[str] = None
    status: Optional[str] = None
    MRP: Optional[float] = None
    BchDtls_Nm: Optional[str] = None
    BchDtls_ExpDt: Optional[date] = None
    BchDtls_WrDt: Optional[date] = None
    created_by: Optional[str] = None
    approved_by: Optional[str] = None

@app.post("/invmaster")
async def create_inv_master(item: InvMasterRequest):
    """
    Creates a new item in invmaster_v2, handling item_group_id logic.
    """
    session = SessionLocal()
    try:
        # 1. Check for existing item based on Barcde/PrdDesc, UnitPrice, Unit, Batch Details

        existing_item_query = None

        if item.Barcde and item.Barcde != "":
            existing_item_query = select(invmaster_v2_table).where(
                invmaster_v2_table.c.abid == item.abid,
                invmaster_v2_table.c.Barcde == item.Barcde,
                invmaster_v2_table.c.UnitPrice == item.UnitPrice,
                invmaster_v2_table.c.vendor_unit == item.vendor_unit,
                invmaster_v2_table.c.BchDtls_Nm == item.BchDtls_Nm,
                invmaster_v2_table.c.BchDtls_ExpDt == item.BchDtls_ExpDt,
                invmaster_v2_table.c.BchDtls_WrDt == item.BchDtls_WrDt
            )
        else:
            existing_item_query = select(invmaster_v2_table).where(
                invmaster_v2_table.c.abid == item.abid,
                invmaster_v2_table.c.vendor_PrdDesc == item.vendor_PrdDesc,
                invmaster_v2_table.c.UnitPrice == item.UnitPrice,
                invmaster_v2_table.c.vendor_unit == item.vendor_unit,
                invmaster_v2_table.c.BchDtls_Nm == item.BchDtls_Nm,
                invmaster_v2_table.c.BchDtls_ExpDt == item.BchDtls_ExpDt,
                invmaster_v2_table.c.BchDtls_WrDt == item.BchDtls_WrDt
            )

        existing_item = session.execute(existing_item_query).first()

        if existing_item:
            # Item exists with same details, return an error
             session.close()
             return {"status_code": 403, "message": "Item already exists." , "response": existing_item._asdict()}

        # 2. Check for existing item based on barcode OR product description to reuse item_group_id
        existing_item_group_query = None

        if item.Barcde and item.Barcde != "":
            existing_item_group_query = select(invmaster_v2_table.c.item_group_id).where(
                invmaster_v2_table.c.abid == item.abid,
                invmaster_v2_table.c.Barcde == item.Barcde
            )
        else:
            existing_item_group_query = select(invmaster_v2_table.c.item_group_id).where(
                invmaster_v2_table.c.abid == item.abid,
                invmaster_v2_table.c.vendor_PrdDesc == item.vendor_PrdDesc
            )

        existing_item_group = session.execute(existing_item_group_query).scalar()

        # 3. Create new item or reuse item_group_id
        if existing_item_group:
            # Reuse existing item_group_id
            item_group_id = existing_item_group
        else:
            # Create new item_group_id
            new_header = {
                "abid": item.abid
            }
            insert_header_stmt = invmaster_header_table.insert().values(new_header)
            result = session.execute(insert_header_stmt)
            session.commit()
            item_group_id = session.execute(select(invmaster_header_table.c.item_group_id).order_by(invmaster_header_table.c.item_group_id.desc()).limit(1)).scalar()


        # Prepare data for insertion into invmaster_v2
        new_item_data = {
            "abid": item.abid,
            "PrdDesc": item.PrdDesc,
            "HsnCd": item.HsnCd,
            "Barcde": item.Barcde,
            "IsServc": item.IsServc,  # No direct mapping in InvMasterRequest, set to None or a default value
            "Unit": item.Unit,
            "UnitPrice": float(item.UnitPrice),
            "GstRt": float(item.GstRt),
            "CesRt": float(item.CesRt),
            "CesNonAdvlAmt": float(item.CesNonAdvlAmt),
            "StateCesRt": float(item.StateCesRt),
            "StateCesNonAdvlAmt": float(item.StateCesNonAdvlAmt),
            "is_PrdSlNo": item.is_PrdSlNo,
            "itemcode": item.itemcode,
            "inv_gl_id": item.inv_gl_id,  # Assuming gl_id maps to inv_gl_id
            "exp_gl_id": None,  # No direct mapping, set to None or a default value
            "inc_gl_id": None,  # No direct mapping, set to None or a default value
            "margin_percent": float(item.margin_percent),
            "selling_price": float(item.selling_price),
            "manufacturer": item.manufacturer,
            "vendor_id": item.vendor_id,
            "vendor_unit": item.vendor_unit,
            "vendor_unitprice": item.vendor_unitprice,
            "unit_conversion_factor": item.unit_conversion_factor,
            "vendor_PrdDesc": item.vendor_PrdDesc,
            "status": item.status,
            "MRP": float(item.MRP) if item.MRP is not None else None,
            "BchDtls_Nm": item.BchDtls_Nm,
            "BchDtls_ExpDt": item.BchDtls_ExpDt,
            "BchDtls_WrDt": item.BchDtls_WrDt,
            "item_group_id": item_group_id,
            "created_by": item.created_by,
            "approved_by": item.approved_by
        }

        # Insert new item into invmaster_v2
        insert_item_stmt = invmaster_v2_table.insert().values(new_item_data)
        result = session.execute(insert_item_stmt)
        session.commit()

        # Get the newly inserted item_id
        item_id = result.inserted_primary_key[0]

        # Update new_item_data with the item_id
        new_item_data["item_id"] = item_id

        session.close()
        return {"status_code": 200, "message": "Item created successfully.", "response": new_item_data}

    except Exception as e:
        session.rollback()
        logging.error(f"Error creating invmaster item: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error creating invmaster item: {str(e)}")


# Data Models (Pydantic)
class ItemReportItem(BaseModel):
    item_id: int
    PrdDesc: str  # Required field, so it will always be present
    HsnCd: Optional[str] = None
    Barcde: Optional[str] = None
    Qty: Optional[float] = None
    Unit: Optional[str] = None
    UnitPrice: Optional[float] = None
    GstRt: Optional[float] = None
    CesRt: Optional[float] = None
    CesNonAdvlAmt: Optional[float] = None
    StateCesRt: Optional[float] = None
    StateCesNonAdvlAmt: Optional[float] = None
    is_PrdSlNo: Optional[str] = None
    itemcode: Optional[int] = None
    inv_gl_id: Optional[int] = None
    exp_gl_id: Optional[int] = None
    inc_gl_id: Optional[int] = None
    margin_percent: Optional[float] = None
    selling_price: Optional[float] = None
    total_quantity: float = 0.0,  # Default value for total_quantity
    item_group_id:int

@app.get("/inv_report", response_model=List[ItemReportItem])
async def get_inv_report(abid: int):
    """
    Fetch all items from invmaster_v2, and include total quantity from itemlist if available.
    """
    # Open database session
    session = SessionLocal()
    try:
        # SQL query to fetch item_id and total quantity from itemlist
        # Use LEFT JOIN to include all invmaster_v2 records
        # If item_id is not found in itemlist, total_quantity will be NULL
        query = text("""
            SELECT
                m.item_id,
                m.PrdDesc,
                m.HsnCd,
                m.Barcde,
                m.Unit,
                m.UnitPrice,
                m.GstRt,
                m.CesRt,
                m.CesNonAdvlAmt,
                m.StateCesRt,
                m.StateCesNonAdvlAmt,
                m.is_PrdSlNo,
                m.itemcode,
                m.inv_gl_id,
                m.exp_gl_id,
                m.inc_gl_id,
                m.margin_percent,
                m.selling_price,
                SUM(i.Qty) AS total_quantity,
                m.item_group_id
            FROM
                invmaster_v2 m
            LEFT JOIN
                itemlist i ON m.item_id = i.item_id AND i.abid = :abid  -- Added abid condition here
            WHERE m.abid = :abid
            GROUP BY
                m.item_id, m.PrdDesc, m.HsnCd, m.Barcde, m.Unit, m.UnitPrice, m.GstRt,
                m.CesRt, m.CesNonAdvlAmt, m.StateCesRt, m.StateCesNonAdvlAmt, m.is_PrdSlNo,
                m.itemcode, m.inv_gl_id, m.exp_gl_id, m.inc_gl_id, m.margin_percent, m.selling_price, m.item_group_id
        """)

        # Execute the query
        results = session.execute(query, {"abid": abid}).fetchall()

        # Construct the response model
        item_report = []
        for row in results:
            try:
                item_report.append(
                    ItemReportItem(
                        item_id=row[0],
                        PrdDesc=row[1],
                        HsnCd=row[2],
                        Barcde=row[3],
                        Qty=None,  # Removed Qty as it's not directly available in invmaster_v2
                        Unit=row[4],
                        UnitPrice=row[5],
                        GstRt=row[6],
                        CesRt=row[7],
                        CesNonAdvlAmt=row[8],
                        StateCesRt=row[9],
                        StateCesNonAdvlAmt=row[10],
                        is_PrdSlNo=row[11],
                        itemcode=row[12],
                        inv_gl_id=row[13],
                        exp_gl_id=row[14],
                        inc_gl_id=row[15],
                        margin_percent=row[16],
                        selling_price=row[17],
                        total_quantity=float(row[18]) if row[18] is not None else 0.0,  # Handle NULL total_quantity
                        item_group_id=row[19]
                    )
                )
            except Exception as e:
                logging.error(f"Error constructing ItemReportItem: {str(e)}")
                raise HTTPException(status_code=500, detail=f"Error constructing ItemReportItem: {str(e)}")

        return item_report
    except Exception as e:
        logging.error(f"Error fetching invmaster data: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error fetching invmaster data: {str(e)}")


class ItemJournalResponse(BaseModel):
    # journal_header_v2 columns
    transaction_type: Optional[str] = None
    source: Optional[str] = None
    posted_date: Optional[date] = None
    cpty_gstin: Optional[str] = None
    cptyid: Optional[int] = None
    cptyname: Optional[str] = None
    ref_no: Optional[str] = None
    irn: Optional[str] = None
    doc_type: Optional[str] = None
    doc_date: Optional[date] = None
    journal_status: Optional[str] = None

    # itemlist columns
    abid: Optional[int] = None
    journal_id: Optional[int] = None
    SlNo: Optional[str] = None
    PrdDesc: Optional[str] = None
    IsServc: Optional[str] = None
    HsnCd: Optional[str] = None
    Barcde: Optional[str] = None
    Qty: Optional[float] = None
    Unit: Optional[str] = None
    UnitPrice: Optional[float] = None
    TotAmt: Optional[float] = None
    Discount: Optional[float] = None
    AssAmt: Optional[float] = None
    GstRt: Optional[float] = None
    IgstAmt: Optional[float] = None
    CgstAmt: Optional[float] = None
    SgstAmt: Optional[float] = None
    CesRt: Optional[float] = None
    CesAmt: Optional[float] = None
    CesNonAdvlAmt: Optional[float] = None
    StateCesRt: Optional[float] = None
    StateCesAmt: Optional[float] = None
    StateCesNonAdvlAmt: Optional[float] = None
    OthChrg: Optional[float] = None
    TotItemVal: Optional[float] = None
    PrdSlNo: Optional[str] = None
    itemcode: Optional[int] = None
    item_id: Optional[int] = None
    gl_id: Optional[int] = None

class ItemJournalRequest(BaseModel):
    abid: int
    item_ids: List[int]

@app.get("/item_journal", response_model=List[ItemJournalResponse])
async def get_item_journal(request: ItemJournalRequest):
    abid = request.abid
    item_ids = request.item_ids
    # Your database query logic here
    """
    Fetch details from itemlist joined with journal_header_v2 for given item_ids and abid.
    """
    # Open database session
    session = SessionLocal()
    try:
        # Construct the SQL query
        query = text(f"""
            SELECT
                jh.transaction_type,
                jh.source,
                jh.posted_date,
                jh.cpty_gstin,
                jh.cptyid,
                jh.cptyname,
                jh.ref_no,
                jh.irn,
                jh.doc_type,
                jh.doc_date,
                jh.journal_status,
                il.abid,
                il.journal_id,
                il.SlNo,
                il.PrdDesc,
                il.IsServc,
                il.HsnCd,
                il.Barcde,
                il.Qty,
                il.Unit,
                il.UnitPrice,
                il.TotAmt,
                il.Discount,
                il.AssAmt,
                il.GstRt,
                il.IgstAmt,
                il.CgstAmt,
                il.SgstAmt,
                il.CesRt,
                il.CesAmt,
                il.CesNonAdvlAmt,
                il.StateCesRt,
                il.StateCesAmt,
                il.StateCesNonAdvlAmt,
                il.OthChrg,
                il.TotItemVal,
                il.OrdLineRef,
                il.PrdSlNo,
                il.itemcode,
                il.item_id,
                il.gl_id
            FROM
                itemlist il
            LEFT JOIN
                journal_header_v2 jh ON il.journal_id = jh.journal_id
            WHERE
                il.abid = :abid
                AND jh.abid = :abid  -- Also filter journal_header by abid
                {"AND il.item_id IN :item_ids" if item_ids else ""}
        """)

        # Prepare parameters for the query
        params = {"abid": abid}
        if item_ids:
            params["item_ids"] = tuple(item_ids)  # Convert list to tuple for IN clause

        # Execute the query
        results = session.execute(query, params).fetchall()

        # Convert results to the response model
        item_journal_report = [
            ItemJournalResponse(
                transaction_type=row[0],
                source=row[1],
                posted_date=row[2],
                cpty_gstin=row[3],
                cptyid=row[4],
                cptyname=row[5],
                ref_no=row[6],
                irn=row[7],
                doc_type=row[8],
                doc_date=row[9],
                journal_status=row[10],
                abid=row[11],
                journal_id=row[12],
                SlNo=row[13],
                PrdDesc=row[14],
                IsServc=row[15],
                HsnCd=row[16],
                Barcde=row[17],
                Qty=row[18],
                Unit=row[19],
                UnitPrice=row[20],
                TotAmt=row[21],
                Discount=row[22],
                AssAmt=row[23],
                GstRt=row[24],
                IgstAmt=row[25],
                CgstAmt=row[26],
                SgstAmt=row[27],
                CesRt=row[28],
                CesAmt=row[29],
                CesNonAdvlAmt=row[30],
                StateCesRt=row[31],
                StateCesAmt=row[32],
                StateCesNonAdvlAmt=row[33],
                OthChrg=row[34],
                TotItemVal=row[35],
                PrdSlNo=row[37],
                itemcode=row[38],
                item_id=row[39],
                gl_id=row[40]
            )
            for row in results
        ]

        return item_journal_report

    except Exception as e:
        print(f"Error: {e}")  # Add print statement for debugging
        raise HTTPException(status_code=500, detail=str(e))

#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------
#---------------------------------------------------------------------------------------------------------------------------------------------------------

#save einvoice data in a seperate table if journal is created(i.e., when einvoice is uploaded using json/tar.gz utility)

einvoice = Table("einvoice", metadata, autoload_with=engine)

# Pydantic model for request validation
class EinvoiceItem(BaseModel):
    abid: int
    journal_id: Optional[int] = None
    cptyid: Optional[int] = None
    ref_no: Optional[str] = None
    AckNo: Optional[int]
    AckDt: Optional[datetime]  # Use str for datetime input; you can convert it later if needed
    Irn: Optional[str]
    SignedInvoice: Optional[str]
    SignedQRCode: Optional[str]
    Status: Optional[str]


@app.post("/einvoice") #later create a get request to show incase user wants to see einvoice (from bills) 
async def create_einvoice(item: EinvoiceItem):  
    # Open database session
    session = SessionLocal()
    
    try:
        # Check if a record with the given Irn and abid already exists
        query = select(einvoice).where(
            (einvoice.c.Irn == item.Irn) & (einvoice.c.abid == item.abid)
        )
        result = session.execute(query).fetchone()
        
        if result:
            if result.Status== item.Status:
                # Record exists, skip insertion or handle as needed
                print(f"Record with Irn {item.Irn} and abid {item.abid} already exists. Skipping insertion.")
                return {"status_code":403,"message": "Record already exists. No new entry created."}
            else:
                # Update einv_Status and journal_status to the new status
                update_stmt = (
                    update(einvoice)
                    .where((einvoice.c.Irn == item.Irn) & (einvoice.c.abid == item.abid))
                    .values(Status=item.Status)
                )
                session.execute(update_stmt)
                session.commit()
                return {"status_code":403,"message": "E-invoice status updated successfully."}

        # Prepare the data to be inserted into the table
        insert_data = {
            "abid": item.abid,
            "journal_id": item.journal_id,
            "cptyid": item.cptyid,
            "ref_no": item.ref_no,
            "AckNo": item.AckNo,
            "AckDt": item.AckDt,
            "Irn": item.Irn,
            "SignedInvoice": item.SignedInvoice,
            "SignedQRCode": item.SignedQRCode,
            "Status": item.Status,
        }
        
        # Insert data into the einvoice table
        session.execute(einvoice.insert().values(insert_data))
        session.commit()  # Commit the transaction
        
        return {"status_code":200,"message": "E-invoice recorded successfully."}

    except Exception as e:
        session.rollback()  # Rollback in case of error
        raise HTTPException(status_code=500, detail=str(e))

    finally:
        session.close()  # Ensure the session is closed

@app.get("/einvoice")
async def get_einvoices(
    abid: int = Query(None, description="Filter by abid"),
    irn: Optional[str] = Query(None, description="Filter by IRN")
    ):
    """
    Fetch e-invoices from the einvoice table based on optional filters.
    """
    session = SessionLocal()
    try:
        # Start building the query
        query = select(einvoice)

        # Add filters if present
        if abid:
            query = query.where(einvoice.c.abid == abid)
        if irn:
            query = query.where(einvoice.c.Irn == irn)

        # Execute the query
        result = session.execute(query).fetchall()

        if not result:
            raise HTTPException(status_code=404, detail="No records found.")

        # Convert result rows to dictionaries
        einvoices = [
            {column.name: value for column, value in zip(einvoice.columns, row)}
            for row in result
        ]

        # Return the data
        return einvoices

    except Exception as e:
        session.rollback()  # Rollback in case of error
        raise HTTPException(status_code=500, detail=str(e))

    finally:
        session.close()  # Ensure the session is closed



if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8000)
