<?php
include 'sec.layer.php';
include '../Cart.class.php'; 
$cart = new Cart; 

// Default redirect page 
$redirectLoc = 'index.php'; 

// dEAL with post data if any to add to cart from index.php

if( isset( $_POST['addToCart'] ) && isset( $_POST['ptid'] ) && isset( $_POST['qty'] ) && isset( $_POST['unitType'] ) ) {
 // Get product details 
	$ptid = $_POST['ptid'];
	$qty = $_POST['qty']; // user wants new qty
	$uom_id = $_POST['unitType'];
	$row_id = $_POST['row_id'];
	
	if( $qty <= 0 ){		
		$updateItem = $cart->remove( $row_id );
		$redirectLoc = ( $updateItem )?  'index.php' : 'carterror.php';
	} else {
	
	$query = $conn->query("SELECT `p`.`productName`, `pt`.* FROM `producttype` AS `pt`
						 JOIN `product` AS `p` 
						 ON `p`.`pid` = `pt`.`pid`
						 WHERE `pt`.`ptid` = " . $ptid ); 
	$row = $query->fetch();

	$itemDetail = array( 
		'id' => $row['ptid'], 
		'productName' => $row['productName'],
		'name' => $row['ptName'],
		'image' => $row['ptImage']
	);		

	$itemType = array();
	
	$query = $conn->query("SELECT `uom_id`, `cost_price` FROM `product_cost_price` WHERE `ptid` = '$ptid' && `endDate` = 'NULL'"); 
	$result = $query->execute();

	for( $i=0; $price = $query->fetch(); $i++ ){
		$itemType[ $price['uom_id'] ] = $price['cost_price'];
	}
	
	$price = getUom_price( $conn, $uom_id, $ptid );

	$itemCart = array(
		'type' => $uom_id,
		'price' => number_format( $price, 2, ".", "," ), 
		'qty' => $qty 	
	);

	// $itemData = array_merge( $itemDetail, $itemType, $itemCart );
	$itemData = $itemDetail + $itemType + $itemCart;
	// Insert item to cart 
	$insertItem = $cart->insert($itemData); 

	// Redirect to cart page
	if ( $insertItem !== FALSE ){
		// $redirectLoc = 'cartview.php';			
		$redirectLoc = 'addprocurement.php';
	} else { 
		$redirectLoc = 'index.php'; 
	}
  }
} 


// Process request based on the specified action 
if(isset($_REQUEST['action']) && !empty($_REQUEST['action'])){ 
    if($_REQUEST['action'] == 'addToCart' && !empty($_REQUEST['id'])){ 
        $ptid = $_REQUEST['id'];
         
        // Get product details 
        $query = $conn->query("SELECT `p`.`productName`, `pt`.* FROM `producttype` AS `pt`
							 JOIN `product` AS `p` 
							 ON `p`.`pid` = `pt`.`pid`
							 WHERE `ptid` = ".$ptid); 
        $row = $query->fetch();
		
        $itemDetail = array( 
            'id' => $row['ptid'], 
			'productName' => $row['productName'],
            'name' => $row['ptName'],
			'image' => $row['ptImage']
        );		
		
		$itemType = array();
		
		$query = $conn->query("SELECT `uom_id`, `cost_price` FROM `product_cost_price` WHERE `ptid` = '$ptid' && `endDate` = 'NULL'"); 
		$result = $query->execute();
		
		for( $i=0; $price = $query->fetch(); $i++ ){
			$itemType[ $price['uom_id'] ] = $price['cost_price'];
		}
	
		//compare the array to find lowest price as starting point
		$minimum = 1000.00;
		$type = '';
		//echo $min;		
		
		foreach( $itemType as $key => $value )
		{
			if ( $value < $minimum && $value > 0 )
			{
				$type = $key;
				$minimum = $value;
			}
			
		}
		
		$itemCart = array(
			'type' => $type,
            'price' => number_format( $minimum, 2, ".", "," ), 
            'qty' => 1 	
		);
		
		// $itemData = array_merge( $itemDetail, $itemType, $itemCart );
		$itemData = $itemDetail + $itemType + $itemCart;
        // Insert item to cart 
        $insertItem = $cart->insert($itemData); 
         
        // Redirect to cart page
		if ( $insertItem !== FALSE ){
			echo 'Cart ItemType Start<br>';
			foreach( $itemType as $key=>$value ){
				echo $key . ' => ' . $value . '<br>';
			}
			echo 'Cart ItemType End<br>';
			$redirectLoc = 'addprocurement.php';			
			//$redirectLoc = 'index.php';
		} else { 
			//$redirectLoc = 'index.php'; 
		}
    } 
	elseif( $_REQUEST['action'] == 'updateCartItem' && !empty($_REQUEST['id'] ) )
	{ 		
		// check if less than 1 and remove from cart if TRUE
		if ( $_REQUEST['qty'] <= 0 ){
			$itemData = array( 
				'rowid' => $_REQUEST['id'], 
				'qty' => $_REQUEST['qty'] 
			); 
			
			//echo '<script>alrt("Are you sure?")</script>';
			$updateItem = $cart->remove($_REQUEST['id']);
			// Return status 
			echo $updateItem? 'ok':'err';
			die;
			
		} else {
			// Update item data in cart 
			$itemData = array( 
				'rowid' => $_REQUEST['id'],
				'qty' => $_REQUEST['qty'] 
			); 
			$updateItem = $cart->update($itemData); 

			// Return status 
			echo $updateItem? 'ok':'err';
			die; 
		}
	} 
	elseif( $_REQUEST['action'] == 'updateCartPrice' && !empty($_REQUEST['id'] ) && !empty( $_REQUEST['price'] ) )
	{ 			
		
			$type= '';
			$cartItems = $cart->get_item( $_REQUEST['id'] );
			foreach ($cartItems as $key=>$value ) {
				if( $_REQUEST['price'] == $value ){
					$type = $key;
				}
			}
		
			$cartArray = $cart->contents();
			/*foreach( $cartArray as $arrayItems ){
					foreach( $arrayItems as $key=>$value ){
						// echo $key . ' => ' . $value .'<br>';
						if( $_REQUEST['price'] == $value ){
							$type = $key;
						}
					}
				}*/
		
			// Update item data in cart 
			$itemData = array( 
				'rowid' => $_REQUEST['id'],
				'type' => $type,
				'price' => $_REQUEST['price']
			); 
		
			$updateItem = $cart->updatePrice($itemData); 

			// Return status 
			echo $updateItem? 'ok':'err';
			die; 	
		
    } 	
	elseif( $_REQUEST['action'] == 'removeCartItem' && !empty($_REQUEST['id']) )
	{ 
        // Remove item from cart 
        $deleteItem = $cart->remove($_REQUEST['id']); 
         
        // Redirect to cart page 
        $redirectLoc = 'addprocurement.php'; 
        // $redirectLoc = 'index.php'; 
    } 
	
	elseif( $_REQUEST['action'] == 'placeOrder' && $cart->total_items() > 0 )
		
	{ 
        $redirectLoc = 'raiseOrder.php';
		$emp_id = $_REQUEST['emp_id'];
			       
                // Insert order info in the database 
                $insertOrder = $conn->query("INSERT INTO purchaseorders ( emp_id, grand_total, created_on, status) VALUES ( '$emp_id', '" . $cart->total() . "', NOW(), 'Pending')"); 
		
                if( $insertOrder !== FALSE ){
					
					//insertLog( $conn, $insertOrder );
                    $orderID = $conn->lastInsertId(); 
                     
                    // Retrieve cart items 
                    $cartItems = $cart->contents(); 
                     
                    // Prepare SQL to insert order items 
                    $sql = ''; 
					
                    foreach( $cartItems as $item ){ 
                        $sql .= "INSERT INTO purchaseorder_items (order_id, ptid, quantity, price, uom_id, status) VALUES ('".$orderID."', '".$item['id']."', '".$item['qty']."', '".$item['price']."', '".$item['type']."', '1');"; 
                    } 
                     
                    // Insert order items in the database 
                    $insertOrderItems = $conn->query( $sql ); 
                     
                    if( $insertOrderItems == TRUE ){ 
						//insertLog( $conn, $insertOrderItems );
                        // Remove all items from cart 
                        $cart->destroy(); 
                         
                        // Redirect to the status page 
                        $redirectLoc = 'purchaseOrderSuccess.php?id='.$orderID; 
                    } else { 
						//insertLog( $conn, $insertOrderItems );
						// Items didnot update correctly in orderItems table
                        $sessData['status']['type'] = 'error'; 
                        $sessData['status']['msg'] = 'Order was not submitted successfully, please try again.'; 
						//insertLog( $conn, $errorMsg );
                    } 
                } else { 
					// Items didnot update correctly in orders table
                    $sessData['status']['type'] = 'error'; 
                    $sessData['status']['msg'] = 'Order was not submitted successfully, please try again.'; 
					//insertLog( $conn, $errorMsg );  
                } 
        $_SESSION['sessData'] = $sessData; 
    } 
} 
 
// Redirect to the specific page 
	if( $cart->total_items() > 0 ){
		$cartArray = $cart->contents();
		foreach( $cartArray as $arrayItems ){
			foreach( $arrayItems as $key => $value ){
				echo $key . ' => ' . $value .'<br>';
			}
		}
	} 

header("Location: $redirectLoc"); 
exit();