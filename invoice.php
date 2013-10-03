	<html>
	<head>
			    
	<style type="text/css">
      #summary 
	  {
	     border-collapse:collapse;
		 width:100%;
	  }

	  #summary th,td
	  {
		 border:1px solid #000;
	  }

      #total 
	  {
	     border-collapse:collapse;
		 width:100%;
	  }
	  
	  #totalvalue
	  {
		 width:15%;
	  }

	  
	  #OrderNumberField
	  {
	     font-weight: bold;
	  }
	  
	  #discounts
	  {
	     color: red;
	  }
	  
	  #custominfofields
	  {
	     font-weight: bold;
	  }
	  
	  #menucontrols
	  {
	     border: 0px;
	  }
	  
	  #signature
	  {
	     font-weight: bold;
	  }
	  
	  #hidemenucontrols
	  {
	     display: none;
	  }
	</style>
	
	</head>
	<body>

	<?
	/*
		Program		: RenderInvoice  Version 1.0
		Programmer	: Dan Gustafson
		Date		: March 30, 2013

		Purpose     : Create an invoice by merging data from flat csv files.  
		Files Needed: Items, Orders and Product csv files
		
		1. Items showed codes, quantity and price
		2. Orders show billing address, customer name and shipping type
		3. Product shows item description
		
		Suggestion  : 
			1. Do a header check to limit the types of files that can be uploaded (such as viruses) 
			2. What security concerns should we have
	*/
    
	// CONSTS
	define(CODE_NOT_FOUND, "-[NOT FOUND]-");  // Possible result in codeToDescription function
	define(SIGNATURE_FILE, "signature.dat");  // Possible result in codeToDescription function
    define(ITEM_FILE, "items.csv");
	define(ORDER_FILE, "orders.csv");
	define(PRODUCT_FILE, "products.csv");
	define(ORDERNUMBER_FILE, "ordernum.dat");
    define(ORDERRANGE_FILE, "orderrange.dat");
    define(ONE_KB, 1024);    
	define(ONE_MB, 1048576);
	//CSV types
	define(MIN_CSVTYPE, 0);
	define(ITEM_CSVTYPE, 0);
	define(ORDER_CSVTYPE, 1);
	define(PRODUCT_CSVTYPE, 2);
	define(MAX_CSVTYPE, 2);
	
	// Known CSV Headers
	define(ITEM_HEADERTYPE,  "\"Order ID\",\"Line ID\",\"Product ID\",\"Product Code\",\"Quantity\",\"Unit Price\"\r\n");
	define(ORDER_HEADERTYPE, "\"Order ID\",\"Date\",\"Numeric Time\",\"Ship Name\",\"Ship Address 1\",\"Ship Address 2\",\"Ship City\",\"Ship State\",\"Ship Country\",\"Ship Zip\",\"Ship Phone\",\"Bill Name\",\"Bill Address 1\",\"Bill Address 2\",\"Bill City\",\"Bill State\",\"Bill Country\",\"Bill Zip\",\"Bill Phone\",\"Email\",\"Referring Page\",\"Entry Point\",\"Shipping\",\"Payment Method\",\"Card Number\",\"Card Expiry\",\"Comments\",\"Total\",\"Link From\",\"Warning\",\"Auth Code\",\"AVS Code\",\"Gift Message\",\"CVV Code\",\"PayPal Auth\",\"PayPal TxID\",\"PayPal Merchant Email\",\"PayPal Payer Status\",\"PayPal Address Status\",\"PayPal Seller Protection\",\"Ship Company\",\"Bill Company\",\"Tax Charge\",\"Shipping Charge\",\"Promotion Discount\",\"Promotion ID\",\"Promotion Type\"\r\n");
    define(PRODUCT_HEADERTYPE, "\"Product ID\",\"Product Code\",\"Description\",\"Order Multiple\",\"Min Order\",\"Max Order\",\"Unit Price\"\r\n");      
	 
	function checkHeaders($CSVtype)
	{
	    if (($CSVType < MIN_CSVTYPE) || ($CSVType > MAX_CSVTYPE)) return false;
        
		$header = "";
		if ($CSVtype == ITEM_CSVTYPE) 
		{
		   $fHandle = fopen(ITEM_FILE, "r");
   		   $header = ITEM_HEADERTYPE;
		}
		else
		if ($CSVtype == ORDER_CSVTYPE)
		{
		   $fHandle = fopen(ORDER_FILE, "r");
		   $header = ORDER_HEADERTYPE;
		}
		else
		if ($CSVtype == PRODUCT_CSVTYPE)
		{
		   $fHandle = fopen(PRODUCT_FILE, "r");
		   $header = PRODUCT_HEADERTYPE;
		}

        $line = fgets($fHandle);
		fclose($fHandle);
		return strcmp($line,$header) == 0;
	}
	
	function SetSignature($companyMessage)
	/*
	    Writes company message to file
		
		SEND: companyMessage - the message to be written
		
		Returns true if was able to write something to a file
	*/
	{
		$signaturefile = fopen(SIGNATURE_FILE, "w");
	    $result = (fwrite($signaturefile, $companyMessage) > 0);
		fclose($signaturefile);
		
		return $result;
	}
    
	function UploadFiles()
	/*
	    Uploads csv, images files to server and also sets the company signature.
		
		File Error Codes:
			Value: 0; There is no error, the file uploaded with success.

			UPLOAD_ERR_INI_SIZE
			Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.

			UPLOAD_ERR_FORM_SIZE
			Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.

			UPLOAD_ERR_PARTIAL
			Value: 3; The uploaded file was only partially uploaded.

			UPLOAD_ERR_NO_FILE
			Value: 4; No file was uploaded.

			UPLOAD_ERR_NO_TMP_DIR
			Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.

			UPLOAD_ERR_CANT_WRITE
			Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.

			UPLOAD_ERR_EXTENSION
			Value: 8; A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.
	*/
	{

	   // Logo
	   $FileName[0] = $_FILES["file"]["name"];
       $FileError[0] = $_FILES["file"]["error"];
       $FileTmp[0]   = $_FILES["file"]["tmp_name"];
	   $Extension = end(explode(".", $_FILES["file"]["name"]));
       $FileSize[0]  = $_FILES["file"]["size"];   
	   
	   // Items.csv
       $FileName[1] = $_FILES["ItemsFile"]["name"];
       $FileError[1] = $_FILES["ItemsFile"]["error"];
       $FileTmp[1]   = $_FILES["ItemsFile"]["tmp_name"];
       $FileSize[1]  = $_FILES["ItemsFile"]["size"];   

	   // Order.csv
       $FileName[2] = $_FILES["OrdersFile"]["name"];
       $FileError[2] = $_FILES["OrdersFile"]["error"];
       $FileTmp[2]   = $_FILES["OrdersFile"]["tmp_name"];
       $FileSize[2]  = $_FILES["OrdersFile"]["size"];   
  
       // Products.csv
       $FileName[3] = $_FILES["ProductsFile"]["name"];
       $FileError[3] = $_FILES["ProductsFile"]["error"];
       $FileTmp[3]   = $_FILES["ProductsFile"]["tmp_name"];
       $FileSize[3]  = $_FILES["ProductsFile"]["size"];   

       for ($i=0; $i<=3; $i++)
       {
          if ($FileError[$i] > 0)
          {
             if ($FileError[$i] != 4) 
			     echo "Return Code: " . $FileError[$i];
          }		  
          else
         {
            if ($FileSize[$i] < ONE_MB * 5) // 5MB file limit
			{
               if ($i == 0) 
			   {
			      // clear logo images
			      if (file_exists("Logo.jpeg")) unlink("Logo.jpeg");
			      if (file_exists("Logo.jpg")) unlink("Logo.jpg");
			      if (file_exists("Logo.gif")) unlink("Logo.gif");
			      if (file_exists("Logo.png")) unlink("Logo.png");
                  move_uploaded_file($FileTmp[$i], "Logo." . $Extension);
			   }
			 
			   else if ($i == 1)
			  {
			     if (file_exists(ITEM_FILE)) unlink(ITEM_FILE);
			      move_uploaded_file($FileTmp[$i], ITEM_FILE);
			  }	
			   else if ($i == 2)
			  {
			    if (file_exists(ORDER_FILE)) unlink(ORDER_FILE);
			    move_uploaded_file($FileTmp[$i], ORDER_FILE);
			  }
			  else if ($i == 3)
			  {
			     if (file_exists(PRODUCT_FILE)) unlink(PRODUCT_FILE);
			     move_uploaded_file($FileTmp[$i], PRODUCT_FILE);
			  } 
			}  // end file size limit
		 }	
		 
       }
	}

    function writeInvoiceHeader()
	/*
	   Shows a logo at top of invoice
	*/
	{
  	   if (file_exists("Logo.jpeg"))
	   {
	      echo "<center>";
		  echo "<img src='Logo.jpeg'/>";
		  echo "</center>";
	   }
	   if (file_exists("Logo.jpg"))
	   {
	      echo "<center>";
		  echo "<img src='Logo.jpg'/>";
		  echo "</center>";
	   }
	   if (file_exists("Logo.gif"))
	   {
	      echo "<center>";
		  echo "<img src='Logo.gif'/>";
		  echo "</center>";
	   }
	   if (file_exists("Logo.png"))
       {
	      echo "<center>";
		  echo "<img src='Logo.png'/>";
		  echo "</center>";
       }	   
	}

	function writeSignature()
	/*
	    Reads a signature from a file and displays it on canvas
	*/
	{   
	   $CompanySignature = fopen(SIGNATURE_FILE, "r");
	   $Signature = fgets($CompanySignature);
	   fclose($CompanySignature);

	   echo "<center>";
	   echo "<span id=signature>";
	   echo $Signature;
	   echo "</span>";
	   echo "</center>";
	   
	}
	
	function writeOrderNumber($orderNumber)
	/*
	    Write an order number to a file
		
		SEND: OrderNumber - the order number to be written
		
		RETURNS: True if write was successful otherwise false
	*/
	{
		$orderfile = fopen(ORDERNUMBER_FILE, "w");
	    $result = (fwrite($orderfile, $orderNumber) > 0);
		fclose($orderfile);
		
		return $result;
	}

	function readOrderNumber()
	/*
	   Returns an order number if file exist.  Otherwise returns a -1
	*/
	{
		if (file_exists(ORDERNUMBER_FILE))
		{
		   $orderfile = fopen(ORDERNUMBER_FILE, "r");
	       $ordernum = fgets($orderfile);
		   fclose($orderfile);
           return $ordernum;		
		}
		else
		   return -1;  // file not found (or not accessible)
	}
	
	function writeMinOrderID($minOrderNumber)
	/*
	    Writes the minimum order number to a file
		
		SEND: minOrderNumber - the first order number in the list
		
		Returns a true if able to write to file otherwise false.
	*/
	{
		$orderfile = fopen(ORDERRANGE_FILE, "w");
	    $result = (fwrite($orderfile, $minOrderNumber) > 0);
		fclose($orderfile);
		
		return $result;
	}

	function writeMaxOrderID($maxOrderNumber)
	/*
	    Writes the maxmum order number to a file
		
		SEND: minOrderNumber - the last order number in the list
		
		Returns a true if able to write to file otherwise false.
	*/
	{
		$orderfile = fopen(ORDERRANGE_FILE, "a");
		$result = (fwrite($orderfile, ",") > 0);
		if ($result)
	       fwrite($orderfile, $maxOrderNumber);
		fclose($orderfile);
		
		return $result;
	}
	
	function getOrderRange(&$orderStart, &$orderEnd)
	/*
	    Returns the first order in the list and the last order in the list
	
	    SEND: orderStart - first order in the list
		      orderEnd   - last order in the list
			  
		Returns true if file exist otherwise false	  
	*/
	{
      $result = (file_exists(ORDERRANGE_FILE));
	  
	  if ($result) 
	  {
         $orderRangeFile = fopen(ORDERRANGE_FILE, "r");
	     $orderrange = fgetcsv($orderRangeFile);
	     list($oS, $oE) = $orderrange;
         fclose($orderRangeFile);
         $orderStart = $oS;
         $orderEnd   = $oE;
      }
      return $result;	  
	}
    
	
	function WriteMaxMinOrderId()
	/*
	    Writes the order ranges, the first and last order in the list.
	    
		Returns true if file exists, otherwise false
	*/
	{
	  $result = (file_exists(ORDER_FILE));
	  
	  if ($result)
	  {
	     $file_orders = fopen(ORDER_FILE, "r");
	     $orderline = fgetcsv($file_orders); // ditch header
	     $firstpass = true;
         while(($orderline = fgetcsv($file_orders)) != FALSE)
	     {
	        list($orders_OrderID, $orders_Date, $orders_NumericTime, $orders_ShipName, $orders_ShipAddress1, $orders_ShipAddress2, $orders_ShipCity, $orders_ShipState, 
		    $orders_ShipCountry, $orders_ShipZip, $orders_ShipPhone,	$orders_BillName, $orders_BillAddress1, $orders_BillAddress2, $orders_BillCity, $orders_BillState, 
		    $orders_BillCountry, $orders_BillZip, $orders_BillPhone,	$orders_Email, $orders_ReferringPage, $orders_EntryPoint, $orders_Shipping, $orders_PaymentMethod, 
		    $orders_CardNumber, $orders_CardExpiry, $orders_Comments, $orders_Total, $orders_LinkFrom, $order_Warning, $orders_AuthCode, $orders_AVSCode, 
		    $orders_GiftMessage, $orders_CVVCode, $orders_PayPalAuth, $orders_PayPalTxID, $orders_PayPalMerchantEmail, $orders_PayPalPayerStatus, 
		    $orders_PayPalAddressStatus, $orders_PayPalSellerProtection, $orders_ShipCompany, $orders_BillCompany, $orders_TaxCharge, $orders_ShippingCharge, 
		    $orders_PromotionDiscount, $orders_PromotionID, $orders_PromotionType) = $orderline;
      
	        if ($firstpass) 
	       {
	          $firstpass = false;
		      writeMinOrderID($orders_OrderID);
	       }
	     }
	     writeMaxOrderID($orders_OrderID);
	     fclose($file_orders);
	  }

	  return $result;

    }
	
    function findFirstOrder(&$orderNum)
	/*
	    Finds the first order in the list
		
		SEND: orderNum - Variable to hold first order
		
		Returns true if file exist, otherwise false
	*/
    {
		$result = (file_exists(ITEM_FILE));
		$orderNum = -1; // default value
		if ($result)
		{
		   $items = fopen(ITEM_FILE, "r");
		   $itemline = fgetcsv($items); // ditch header
		   $itemline = fgetcsv($items);
		   list($OrderID, $LineID, $ProductID, $ProductCode, $Quantity, $UnitPrice) = $itemline;
		   fclose($items);
		   $orderNum = $OrderID;
		}
		
		
		return $result;
    }
	
    function codeToDescription($Code)
	/*
	    Converts a product code to a description
		
		SEND: $Code - item code
		
		Returns item description upon success or "-[NOT FOUND]-"
	*/
	{
	  $result = (file_exists(PRODUCT_FILE));
	  if ($result)
	  {
	     $products = fopen(PRODUCT_FILE, "r");
         while(($productline = fgetcsv($products)) != FALSE)
	     {
	        list($pProductID, $pProductCode, $pDescription, $pOrderMultiple, $pMinOrder, $pMaxOrder, $pUnitPrice) = $productline;
		 
		    if ($Code == $pProductCode) 
		    {
		       fclose($products);
		       return $pDescription;    
		    }
	     }
      
	     fclose($products);
	  }
	  
	  return CODE_NOT_FOUND;
	}
	
	function writeCustomership($order_number)
	/*
	    Write shipping address on invoice
		
		SEND: order_number - Order number of the invoice we want to print
		
		Returns false if file was not found
	*/
	{
	  $result = (file_exists(ORDER_FILE));
	  
      if ($result) 
	  {
	     $file_orders = fopen(ORDER_FILE, "r");
         while(($orderline = fgetcsv($file_orders)) != FALSE)
	     {
	        list($orders_OrderID, $orders_Date, $orders_NumericTime, $orders_ShipName, $orders_ShipAddress1, $orders_ShipAddress2, $orders_ShipCity, $orders_ShipState, 
		    $orders_ShipCountry, $orders_ShipZip, $orders_ShipPhone,	$orders_BillName, $orders_BillAddress1, $orders_BillAddress2, $orders_BillCity, $orders_BillState, 
		    $orders_BillCountry, $orders_BillZip, $orders_BillPhone,	$orders_Email, $orders_ReferringPage, $orders_EntryPoint, $orders_Shipping, $orders_PaymentMethod, 
		    $orders_CardNumber, $orders_CardExpiry, $orders_Comments, $orders_Total, $orders_LinkFrom, $order_Warning, $orders_AuthCode, $orders_AVSCode, 
		    $orders_GiftMessage, $orders_CVVCode, $orders_PayPalAuth, $orders_PayPalTxID, $orders_PayPalMerchantEmail, $orders_PayPalPayerStatus, 
		    $orders_PayPalAddressStatus, $orders_PayPalSellerProtection, $orders_ShipCompany, $orders_BillCompany, $orders_TaxCharge, $orders_ShippingCharge, 
		    $orders_PromotionDiscount, $orders_PromotionID, $orders_PromotionType) = $orderline;

		    if ($order_number == $orders_OrderID) 
		    {
		 
               echo "<span id=custominfofields>";
			   echo "Order Number: ";    
		       echo "</span>";
			   echo $orders_OrderID . "<br>";

		       echo "<span id=custominfofields>";
			   echo "Date: ";    
		       echo "</span>";
			   echo $orders_Date . "<br>";

		       echo "<span id=custominfofields>";
			   echo "Ship To: <br>";    
		       echo "</span>";

        	   echo $orders_ShipName . "<br>";		 
        	   echo $orders_ShipAddress1 . "<br>";
               if ($orders_ShipAddress2 != "") 			 
        	      echo $orders_ShipAddress2 . "<br>";

			   echo $orders_ShipCity . " " . $orders_ShipState . " " . $orders_ShipZip . "<br>";		 
        	   echo $orders_ShipCountry . "<br>";
			   echo $orders_ShipPhone . "<br>";

		       echo "<span id=custominfofields>";
			   echo "Via: ";    
		       echo "</span>";
			   echo $orders_Shipping . "<br>";			 
		    }
      
	    }	  
        fclose($file_orders);
	  }
	  
	  return $result;
	}
	
	function printItemCodes($Items, $Order_Number)
	/*
		Prints item codes for a specific order.
		
		SEND : $Items - file handle to csv file
			 : $Order_Number - Order number for items to print
	*/
	{
		$canvasTouched    = false;          // We wrote to the invoice - ** check to see if we can elimnate this var
		$doHeader         = true;           // Write header
		$total            = 0.0;            // Order total
		$discounts        = false;     
		$shippingfound    = false;     
		$needsubtotal     = true;      
		$orderfound       = false;
		while(($itemline = fgetcsv($Items)) != FALSE)
		{
			list($OrderID, $LineID, $ProductID, $ProductCode, $Quantity, $UnitPrice) = $itemline;

		    if (($canvasTouched) && ($ProductCode == "Shipping")) // We have written to the Canvas and now we have encounted a shipping code
			{
			   $shippingfound = true;
            }
			
			if (!$orderfound)
			   $orderfound = ($OrderID == $Order_Number);  // We're looking for a specific order number

		   if ($OrderID == $Order_Number)
			{
			$discounts = (($shippingfound) && ($ProductCode != "Shipping"));
			
				if ($doHeader) 
				{
				   writeInvoiceHeader();
			       writeCustomership($OrderID);
				
				   echo "<table id=summary>";
				   echo "<tr>";
				   echo "<th> Item </th>";
				   echo "<th> Code </th>";
				   echo "<th> Quantity </th>";
				   echo "<th> Unit Price </th>";
				   echo "<th>Price </th>";
             	   echo "</tr>";
                   $doHeader = false;				   
				}
				echo "<tr>";
				$description = codeToDescription($ProductCode);
				echo "<td>";
				if ($description == CODE_NOT_FOUND)
				    echo "&nbsp";
				else 
				    echo $description;
                echo "</td>";
				echo "<td>";
				echo $ProductCode;
                echo "</td>";
				echo "<td>";
				echo $Quantity;
                echo "</td>";
				echo "<td>";
				echo $UnitPrice;
                echo "</td>";
				echo "<td>";
				if ($discounts) 
				{
				   echo "<span id=discounts>";
				   echo "-";
				}   
				echo  "$".number_format(substr($UnitPrice,1) * $Quantity, 2, ".",",");
				if ($discount) echo "</span>";
				if (!$discounts)
				{
				   $total = $total + substr($UnitPrice,1) * $Quantity;
				} 
				else
				{
				   $total = $total - substr($UnitPrice,1) * $Quantity;
				}
                echo "</td>";
                echo "</tr>";				
				$canvasTouched = true;
			}
		}
		if ($orderfound)		// if we found an order but now we are at the end of the item list
		{ 
           echo "</table>";  // close our table		
		   echo "<br> <br>";
		   echo "<table id=total>";
		   echo "<tr>";
		   echo "<td id=totalfield>";
		   echo "Total: ";
		   echo "</td>";
		   echo "<td id=totalvalue>";
   		   echo "$" . $total;
           echo "</td>";
		   echo "</tr>";
		   echo "</table>";
           $orderfound = false;		   
        }
		return $canvasTouched;
	}
    
	function writeMenuControls($IsPrintReady)
	/*
	    Print menu button controls at bottom of form
	*/
	{
	   if($IsPrintReady == "Yes")
	      echo "<div id=hidemenucontrols>";
		
	   echo "<div id=hideallcontrols>";
	   echo "<table>";
	   echo "<tr>";
	   echo "<td id=\"menucontrols\">";
	
	   echo "<form action=\"" . $_SERVER['PHP_SELF ' ] . "\" method=\"post\">";
	   echo "<input type=\"hidden\" name=\"direction\" value=\"subtract\" />";
	   echo "<input type=\"hidden\" name=\"formaction\" value=\"directionevent\" />";
	   echo "<input type=\"submit\" name=\"submit\" value=\"<-Previous Order\" />";
	   echo "</form>";
	   echo "</td>";
	   echo "<td id=\"menucontrols\">";	
	   echo "<form action=\"" . $_SERVER['PHP_SELF'] ."\" method=\"post\">";
	   echo "<input type=\"hidden\" name=\"direction\" value=\"add\" />";
	   echo "<input type=\"hidden\" name=\"formaction\" value=\"directionevent\" />";
	   echo "<input type=\"submit\" name=\"submit\" value=\"Next Order->\" />";
	   echo "</form>";
	   echo "</td>";

	   echo "<td id=\"menucontrols\">";	
	   echo "<form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\">";
	   echo "Order Number: <input type=\"text\" name=\"exactorder\">";
	   echo "<input type=\"hidden\" name=\"searchtype\" value=\"exactsearch\" />";
	   echo "<input type=\"hidden\" name=\"formaction\" value=\"searchevent\" />";
	   echo "<input type=\"submit\" name=\"submit\" value=\"View Order\" />";
	   echo "</form>";
	   echo "</td>";
	   
	   echo "<td id=\"menucontrols\">";	
	   echo "<form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\">";
	   echo "<input type=\"hidden\" name=\"printready\" value=\"Yes\" />";
	   echo "<input type=\"hidden\" name=\"formaction\" value=\"printevent\" />";
	   echo "<input type=\"submit\" name=\"submit\" value=\"Make Print Ready\" />";
	   echo "</form>";
	   echo "</td>";
	   echo "</tr>";
	   echo "</table>";
	   if($IsPrintReady == "Yes")
	      echo "</div>";
	
	}
	/*
	while(($itemline = fgetcsv($items)) != FALSE)
	{
		list($OrderID, $LineID, $ProductID, $ProductCode, $Quantity, $UnitPrice) = $itemline;
		echo $OrderID . " " . $LineID . " " . $ProductID . " " . $ProductCode . " " . $Quantity . " " . $UnitPrice . "<br>"; 
	}
	*/

	$Uploading = $_POST['uploadform'];
	if ($Uploading == "IsUploading")
	{
       UploadFiles();
	   $companyMessage = $_POST['Signaturetext'];
	   if ($companyMessage != "") 
	      SetSignature($companyMessage);
	}
 	
	if ((!file_exists(ITEM_FILE)) || (!file_exists(ORDER_FILE)) || (!file_exists(PRODUCT_FILE))) {
	   echo "csv files not present.  Exiting program.";
	   exit(0);
	}

	if (!checkHeaders(ITEM_CSVTYPE))
	{
	   echo ITEM_FILE . " not found.  Exiting program.";
	   exit(0);
	}

	if (!checkHeaders(ORDER_CSVTYPE))
	{
	   echo ORDER_FILE . " not found.  Exiting program.";
	   exit(0);
	}

	if (!checkHeaders(PRODUCT_CSVTYPE))
	{
	   echo PRODUCT_FILE . " not found.  Exiting program.";
	   exit(0);
	}
	
    getOrderRange($orderStart, $orderEnd);
	$orderNumber = $orderStart;
    $formAction = $_POST['formaction'];
	$direction  = $_POST['direction'];
	$exactOrder = $_POST['exactorder'];
    $searchType = $_POST['searchtype'];	
	$MakePrintReady = $_POST['printready'];
    if ($MakePrintReady == "Yes")
	   $searchType != "exactsearch";
	if ($formAction == "directionevent") 
	{
	  $orderNumber = readOrderNumber();
	   $orderStart = 0;
	   $orderEnd   = 0;
       getOrderRange($orderStart, $orderEnd);
	   
	   if($direction == "add") 
	   {
	      $orderNumber++;
	      if ($orderNumber > $orderEnd) $orderNumber = $orderStart; // wrap around
	   }
	   else
	   {
	      $orderNumber--;
	      if ($orderNumber < $orderStart) $orderNumber = $orderEnd; // wrap around
	   }
		  
	   	  
	}
	else if ($formAction == "searchevent")
	;                                      // do nothing we have the current order number
	else if ($formAction == "printevent")
	{
	   $orderNumber = readOrderNumber();
	   $MakePrintReady = "Yes";
	   $searchType = "exactsearch";         // find the last search order id
       $exactOrder = $orderNumber;
	   if (($exactOrder < $orderStart) || ($exactOrder > $orderEnd)) $exactOrder = $orderStart;
	}	   
	else
    {	
	   WriteMaxMinOrderId();
	   findFirstOrder($orderNumber);
	}
	writeOrderNumber($orderNumber);

	$items = fopen("items.csv", "r");
	if ($searchType != "exactsearch") 
	{
	   $orderNotFound = false;
	   while (!printItemCodes($items, $orderNumber))
	   {
	      $orderNotFound = true;
	      if($direction == "add") 
	         $orderNumber++;
	      else
	         $orderNumber--;
	   
	      fseek($items,0); // go back to begining of file
	      if(printItemCodes($items, $orderNumber)) break;
	   }
	   if ($orderNotFound) writeOrderNumber($orderNumber);
	}
	else 
	{
	   if (!printItemCodes($items, $exactOrder)) // Looking for exactly this order
	      echo "order not found! <br>";
	   else
	      writeOrderNumber($exactOrder);  
	}
	fclose($items);

	if (file_exists(SIGNATURE_FILE))
	   writeSignature();
	
	writeMenuControls($MakePrintReady);
	
	?>
	</body>
	</html>
