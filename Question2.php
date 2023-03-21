<?php 
$userId = 123;
$amount = 300;
$pointsUsed = 60;
$currency = "MYR";
payment($userId, $amount, $pointsUsed, $currency);

function payment($userId, $amount, $pointsUsed, $currency)
{
	try
	{
		if($pointsUsed != 0)
		{
			// Use older points first.
			$expiryDate = date('Y-m-d H:i:s', strtotime('-1 year')); // Get non expired
			$getPointsSql = "SELECT * FROM points WHERE user_id = '$userId' AND expiry_date > '$expiryDate' AND unused_points > 0 ORDER BY expiry_date ASC";
			$getPoints = connMySql($getPointsSql);

			$pointCalc = $pointsUsed;
			$idList = []; // ID list for those that have their points deducted.
			foreach($getPoints as $points)
			{
				if($pointCalc <= 0) //Complete
				{
					break;
				}

				$pointCalc -= $points['unused_points'];
				array_push($idList, $points['txn_id']);
			}

			// Negative is the balance that is returned for the last ID. i.e. user has 200 points, redeemed 100.
			// Based on $pointCalc -= $points->amount;, $pointCalc is at -100, we make that positive and it becomes 100, user has 100 points left.
			foreach($idList as $key => $txnId)
			{	
				$points = 0;
				if($key === array_keys($idList)[count($idList)-1]) // Alternative to array_key_last
				{
					$points = -$pointCalc; //Balance points, if its 0 return 0;
			    }
				$redeemPointsSql = "UPDATE points SET unused_points = '$points' WHERE txn_id = '$txnId' AND user_id = '$userId'";
				$redeemPoints = connMySql($redeemPointsSql);
			}

			$discountedAmount = $pointsUsed/100;
			$amount = convertCurrency($amount, $currency, 'USD') - $discountedAmount; // Deduct the discounted amount, new amount for user to pay.
			// in USD
		}

		//** Payment **//
		$txnId = uniqid(); //Gen one for the time being
		$amount = convertCurrency($amount, 'USD', $currency);
		$paymentSql = "INSERT INTO payment (txn_id, user_id, amount, is_completed, currency) VALUES ('$txnId', '$userId', '$amount', 1, '$currency')"; // For now assume payment done
		$payment = connMySql($paymentSql);

		// If payment not done allow cronjob to settle rewarding points via same rewardingPoints function.
		$checkPaymentSql = "SELECT * FROM payment WHERE txn_id = '$txnId'";
		$checkPayment = connMySql($checkPaymentSql);

		if($checkPayment->fetch_array()['is_completed'] == 1)
		{
			$amount = convertCurrency($amount, $currency, 'USD');
			rewardingPoints($txnId, $amount, $userId);
		}

		echo "success";

	}
	catch(Exception $e)
	{
		echo "Internal Error at payment function.";
	}

}

/** Rewards users with points when payment is complete, when the payment is complete, run this function **/
function rewardingPoints($txnId, $points, $userId)
{
	try
	{
		$points = floor($points); // Make whole number
		$expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));
		$insertPointsSql = "INSERT INTO points (txn_id, user_id, points, unused_points, expiry_date) VALUES ('$txnId', '$userId', '$points', '$points', '$expiryDate')";
		$insertPoints = connMySql($insertPointsSql);

	}
	catch(Exception $e)
	{
		echo "Internal Error at rewardingPoints function.";
	}


}

function convertCurrency($amount, $currency1, $currency2)
{
	$convCurrencyUrl = "https://api.exchangerate.host/convert?from=".$currency1."&to=". $currency2;
	$convCurrency = file_get_contents($convCurrencyUrl);
	$response = json_decode($convCurrency, true);
	$amount = $amount * $response['info']['rate'];

	return $amount;
}

function connMySql($sql)
{
	try
	{
		$connectDb = mysqli_connect("localhost", "root", "", "pawprints"); //Ideally env variables is used
		$sqlToSend = $sql;
		$response = $connectDb->query($sqlToSend);

		$connectDb->close();

		return $response;
	}
	catch(Exception $e)
	{
		echo "Internal Error at mySql connection.";
	}

}

 ?>