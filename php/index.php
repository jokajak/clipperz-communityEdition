<?php
/*

Copyright 2006-2008 Clipperz Srl

This file is part of Clipperz Community Edition.
Clipperz Community Edition is a web-based password manager and a
digital vault for confidential data.
For further information about its features and functionalities please
refer to http://www.clipperz.com

* Clipperz Community Edition is free software: you can redistribute
  it and/or modify it under the terms of the GNU Affero General Public
  License as published by the Free Software Foundation, either version
  3 of the License, or (at your option) any later version.

* Clipperz Community Edition is distributed in the hope that it will
  be useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the GNU Affero General Public License for more details.

* You should have received a copy of the GNU General Public License
  along with Clipperz Community Edition.  If not, see
  <http://www.gnu.org/licenses/>.

*/

	include "./configuration.php";
	include "./objects/class.database.php";
	include "./objects/class.user.php";
	include "./objects/class.record.php";
	include "./objects/class.recordversion.php";
	include "./objects/class.onetimepassword.php";
	include "./objects/class.onetimepasswordstatus.php";

//-----------------------------------------------------------------------------
//	'dec2base', 'base2dec' and 'digits' are functions found on the following 
//	PHP manual page: http://ch2.php.net/manual/en/ref.bc.php
//

function dec2base($dec, $base, $digits=FALSE) {
	if ($base<2 or $base>256) {
		die("Invalid Base: ".$base);
	}
	
	bcscale(0);
	$value="";
	if (!$digits) {
		$digits = digits($base);		
	}

	while ($dec > $base-1) {
		$rest = bcmod($dec, $base);
		$dec = bcdiv($dec, $base);
		$value = $digits[$rest].$value;
	}

	$value=$digits[intval($dec)].$value;

	return (string)$value;
}

//.............................................................................

// convert another base value to its decimal value
function base2dec($value, $base, $digits=FALSE) {
	if ($base<2 or $base>256) {
		die("Invalid Base: ".$base);
	}
	
	bcscale(0);
	if ($base<37) {
		$value=strtolower($value);
	}

	if (!$digits) {
		$digits=digits($base);
	}

	$size = strlen($value);
	$dec="0";
	for ($loop=0; $loop<$size; $loop++) {
		$element = strpos($digits, $value[$loop]);
		$power = bcpow($base, $size-$loop-1);
		$dec = bcadd($dec, bcmul($element,$power));
	}

	return (string)$dec;
}

//.............................................................................

function digits($base) {
	if ($base>64) {
		$digits="";
		for ($loop=0; $loop<256; $loop++) {
			$digits.=chr($loop);
		}
	} else {
		$digits ="0123456789abcdefghijklmnopqrstuvwxyz";
		$digits.="ABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
	}

	$digits=substr($digits,0,$base);

	return (string)$digits;
}

//-----------------------------------------------------------------------------

function clipperz_hash($value) {
	return hash("sha256", hash("sha256", $value, true));
}

//-----------------------------------------------------------------------------

function clipperz_randomSeed() {
	$result;

	srand((double) microtime()*1000000);
	$result = "";
	
	while(strlen($result) < 64) {
		$result = $result.dec2base(rand(), 16);
	}
	
	$result = substr($result, 0, 64);
	
	return $result;
}

//-----------------------------------------------------------------------------

function updateUserCredentials($parameters, &$user) {
	$user->username =		$parameters["C"];
	$user->srp_s =			$parameters["s"];
	$user->srp_v =			$parameters["v"];
	$user->auth_version =	$parameters["version"];
}

function updateUserData($parameters, &$user) {
	$user->header =		$parameters["header"];
	$user->statistics =	$parameters["statistics"];
	$user->version =	$parameters["version"];
	$user->lock =		$parameters["lock"];
}

function updateRecordData($parameters, &$record, &$recordVersion) {
	$recordData = $parameters["record"];
	$record->reference =	$recordData["reference"];
	$record->data =			$recordData["data"];
	$record->version =		$recordData["version"];

	$recordVersionData = $parameters["currentRecordVersion"];
	$recordVersion->reference =				$recordVersionData ["reference"];
	$recordVersion->data =					$recordVersionData ["data"];
	$recordVersion->version =				$recordVersionData ["version"];
	$recordVersion->previous_version_id =	$recordVersionData ["previousVersion"];
	$recordVersion->previous_version_key =	$recordVersionData ["previousVersionKey"];
}

//-----------------------------------------------------------------------------

function updateOTPStatus(&$otp, $status) {
	$otpStatus = new onetimepasswordstatus();
	$selectedStatuses = $otpStatus->GetList(array(array("code", "=", $status)));
	$otpStatus = $selectedStatuses[0];
	$otp->SetOnetimepasswordstatus($otpStatus);
}

function updateOTP($parameters, &$otp, $status) {
	$otp->reference 	= $parameters["reference"];
	$otp->key			= $parameters["key"];
	$otp->key_checksum	= $parameters["keyChecksum"];
	$otp->data			= $parameters["data"];
	$otp->version		= $parameters["version"];

	updateOTPStatus($otp, $status);
}

function resetOTP(&$otp, $status) {
	$otp->data			= "";
	updateOTPStatus($otp, $status);
	$otp->Save();
}

//-----------------------------------------------------------------------------

function fixOTPStatusTable() {
	$otpStatus = new onetimepasswordstatus();
	$otpStatusList = $otpStatus->GetList();
	if (count($otpStatusList) != 4) {
		$otpStatus->DeleteList();
		
		$otpStatus->code = "ACTIVE";	$otpStatus->name = "Active";	$otpStatus->description	= "Active";		$otpStatus->SaveNew();
		$otpStatus->code = "REQUESTED";	$otpStatus->name = "Requested";	$otpStatus->description	= "Requested";	$otpStatus->SaveNew();
		$otpStatus->code = "USED";		$otpStatus->name = "Used";		$otpStatus->description	= "Used";		$otpStatus->SaveNew();
		$otpStatus->code = "DISABLED";	$otpStatus->name = "Disabled";	$otpStatus->description	= "Disabled";	$otpStatus->SaveNew();
	}
}

//-----------------------------------------------------------------------------

function arrayContainsValue($array, $value) {
	$object = NULL;
	for ($i=0; $i<count($array); $i++) {
		if ($array[$i] == $value) {
			$object = $value;
		}
	}

	return !is_null($object);
}

//-----------------------------------------------------------------------------

	$result = Array();
	
	session_start();
	
	$method = $_POST['method'];

	if (get_magic_quotes_gpc()) {
		$parameters = json_decode(stripslashes($_POST['parameters']), true);
	} else {
		$parameters = json_decode($_POST['parameters'], true);
	}

	switch($method) {
		case "registration":
			$message = $parameters["message"];

			if ($message == "completeRegistration") {
				$user = new user();
			
				updateUserCredentials($parameters["credentials"], $user);
				updateUserData($parameters["user"], $user);
				$user->Save();
			
				$result["lock"] = $user->lock;
				$result["result"] = "done";
			}
			break;
			
		case "handshake":
			$srp_g = "2";
			$srp_n = base2dec("115b8b692e0e045692cf280b436735c77a5a9e8a9e7ed56c965f87db5b2a2ece3", 16);
			if ($parameters["parameters"] && !is_null($parameters["parameters"]["parameters"])) {
				$parameters = $parameters["parameters"];
				$gammaClient = true;
			}

			$message = $parameters["message"];

			//=============================================================
			if ($message == "connect") {
				$user= new user();
				$_SESSION["C"] = $parameters["parameters"]["C"];
				$_SESSION["A"] = $parameters["parameters"]["A"];

				$userList = $user->GetList(array(array("username", "=", $_SESSION["C"])));
			
				if (count($userList) == 1) {
					$currentUser = $userList[ 0 ];

					if (!is_null($_SESSION["otpId"])) {
						$otp = new onetimepassword();
						$otp = $otp->Get($_SESSION["otpId"]);
						
						if ($otp->GetUser()->userId != $currentUser->userId) {
							throw new Exception("User missmatch between the current session and 'One Time Password' user");
						} else if ($otp->GetOnetimepasswordstatus()->code != "REQUESTED") {
							throw new Exception("Tring to use an 'One Time Password' in the wrong state");
						}

						resetOTP($otp, "USED");
						$result["oneTimePassword"] = $otp->reference;
					}

					$_SESSION["s"] = $currentUser->srp_s;
					$_SESSION["v"] = $currentUser->srp_v;
					$_SESSION["userId"] = $currentUser->userId;
				} else {
					$_SESSION["s"] = "112233445566778899aabbccddeeff00112233445566778899aabbccddeeff00";
					$_SESSION["v"] = "112233445566778899aabbccddeeff00112233445566778899aabbccddeeff00";
				}

				$_SESSION["b"] = clipperz_randomSeed();
//				$_SESSION["b"] = "5761e6c84d22ea3c5649de01702d60f674ccfe79238540eb34c61cd020230c53";
				$_SESSION["B"] = dec2base(bcadd(base2dec($_SESSION["v"], 16), bcpowmod($srp_g, base2dec($_SESSION["b"], 16), $srp_n)), 16);

				$result["s"] = $_SESSION["s"];
				$result["B"] = $_SESSION["B"];
				
			//=============================================================
			} else if ($message == "credentialCheck") {
				$u = clipperz_hash(base2dec($_SESSION["B"],16));
				$A = base2dec($_SESSION["A"], 16);
				$S = bcpowmod(bcmul($A, bcpowmod(base2dec($_SESSION["v"], 16), base2dec($u, 16), $srp_n)), base2dec($_SESSION["b"], 16), $srp_n);
				$K = clipperz_hash($S);
				$M1 = clipperz_hash($A.base2dec($_SESSION["B"],16).$K);

//$result["B"] = $_SESSION["B"];
//$result["u"] = $u;
//$result["A"] = $A;
//$result["S"] = $S;
//$result["K"] = $K;
//$result["M1"] = $M1;
//$result["_M1"] = $parameters["parameters"]["M1"];

				if ($M1 == $parameters["parameters"]["M1"]) {
					$_SESSION["K"] = $K;
					$M2 = clipperz_hash($A.$M1.$K);

					$result["M2"] = $M2;
					$result["connectionId"] = "";
					$result["loginInfo"] = array();
					$result["loginInfo"]["latest"] = array();
					$result["loginInfo"]["current"] = array();
					$result["offlineCopyNeeded"] = "false";
					$result["lock"] = "----";
				} else {
					$result["error"] = "?";
				}
			//=============================================================
			} else if ($message == "oneTimePassword") {
//{
//	"message":"oneTimePassword",
//	"version":"0.2",
//	"parameters":{
//		"oneTimePasswordKey":"06dfa7f428081f8b2af98b0895e14e18af90b0ef2ff32828e55cc2ac6b24d29b",
//		"oneTimePasswordKeyChecksum":"60bcba3f72e56f6bb3f0ff88509b9a0e5ec730dfa71daa4c1e892dbd1b0c360d"
//	}
//}
				$otp = new onetimepassword();
				$otpList = $otp->GetList(array(array("key", "=", $parameters["parameters"]["oneTimePasswordKey"])));

				if (count($otpList) == 1) {
					$currentOtp = $otpList[0];
					
					if ($currentOtp->GetOnetimepasswordstatus()->code == "ACTIVE") {
						if ($currentOtp->key_checksum == $parameters["parameters"]["oneTimePasswordKeyChecksum"]) {
							$_SESSION["userId"] = $currentOtp->GetUser()->userId;
							$_SESSION["otpId"]	= $currentOtp->onetimepasswordId;
							
							$result["data"] = $currentOtp->data;
							$result["version"] = $currentOtp->version;

							resetOTP($currentOtp, "REQUESTED");
						} else {
							resetOTP($currentOtp, "DISABLED");
							throw new Exception("The requested One Time Password has been disabled, due to a wrong keyChecksum");
						}
					} else {
						throw new Exception("The requested One Time Password was not active");
					}
				} else {
        			throw new Exception("The requested One Time Password has not been found");
				}

			//=============================================================
			}

			break;

		case "message":
			if ($parameters["parameters"] && !is_null($parameters["parameters"]["parameters"])) {
				$parameters = $parameters["parameters"];
				$gammaClient = true;
			}
			if ($parameters["srpSharedSecret"] == $_SESSION["K"]) {
				$message = $parameters["message"];
				
				//=============================================================
				if ($message == "getUserDetails") {
//{"message":"getUserDetails", "srpSharedSecret":"f18e5cf7c3a83b67d4db9444af813ee48c13daf4f8f6635397d593e52ba89a08", "parameters":{}}
					$user = new user();
					$user = $user->Get($_SESSION["userId"]);
					
					$result["header"] =		$user->header;
					$records = $user->GetRecordList();
					foreach ($records as $record)
					{
						$recordStats["updateDate"] = $record->update_date;
						$recordsStats[$record->reference] = $recordStats;
					}
					$result["recordsStats"] = $recordsStats;
					$result["statistics"] =	$user->statistics;
					$result["version"] =	$user->version;

				//=============================================================
				} else if ($message == "addNewRecords") {
/*
//{
//	"message":"addNewRecords",
//	"srpSharedSecret":"b58fdf62acebbcb67f63d28c0437f166069f45690c648cd4376a792ae7a325f7",
//	"parameters":{
//		"records":[
//			{
//				"record":{
//					"reference":"fda703707fee1fff42443124cd0e705f5bea0ac601758d81b2e832705339a610",
//					"data":"OBSGtcb6blXq/xaYG.....4EqlQqgAvITN",
//					"version":"0.3"
//				},
//				"currentRecordVersion":{
//					"reference":"83ad301525c18f2afd72b6ac82c0a713382e1ef70ac69935ca7e2869dd4ff980",
//					"recordReference":"fda703707fee1fff42443124cd0e705f5bea0ac601758d81b2e832705339a610",
//					"data":"NXJ5jiZhkd0CMiwwntAq....1TjjF+SGfE=",
//					"version":"0.3",
//					"previousVersion":"3e174a86afc322271d8af28bc062b0f1bfd7344fad01212cd08b2757c4b199c4",
//					"previousVersionKey":"kozaaGCzXWr71LbOKu6Z3nz520V..5U85tSBvb+u44twttv54Kw=="
//				}
//			}
//		],
//		"user":{
//			"header":"{\"reco...ersion\":\"0.1\"}",
//			"statistics":"rKI6nR6iqggygQJ3SQ58bFUX",
//			"version":"0.3",
//			"lock":"----"
//		}
//	}
//}
*/
					$user = new user();
					$record = new record();
					$recordVersion = new recordversion();

					$user = $user->Get($_SESSION["userId"]);
					updateUserData($parameters["parameters"]["user"], $user);

					$recordParameterList = $parameters["parameters"]["records"];
					$c = count($recordParameterList);
					for ($i=0; $i<$c; $i++) {
						updateRecordData($recordParameterList[$i], $record, $recordVersion);

						$record->SaveNew();
						$recordVersion->SaveNew();

						$record->AddRecordversion($recordVersion);
						$user->AddRecord($record);
						
						$record->Save();
						$recordVersion->Save();
					}

					$user->Save();

					$result["lock"] = $user->lock;
					$result["result"] = "done";

				//=============================================================
				} else if ($message == "getRecordDetail") {
//{
//	"message":"getRecordDetail",
//	"srpSharedSecret":"4c00dcb66a9f2aea41a87e4707c526874e2eb29cc72d2c7086837e53d6bf2dfe",
//	"parameters":{
//		"reference":"740009737139a189cfa2b1019a6271aaa39467b59e259706564b642ff3838d50"
//	}
//}
//
//	result = {
//		currentVersion:{
//			reference:"88943d709c3ea2442d4f58eaaec6409276037e5a37e0a6d167b9dad9e947e854",
//			accessDate:"Wed, 13 February 2008 14:25:12 UTC",
//			creationDate:"Tue, 17 April 2007 17:17:52 UTC",
//			version:"0.2",
//			data:"xI3WXddQLFtL......EGyKnnAVik",
//			updateDate:"Tue, 17 April 2007 17:17:52 UTC",
//			header:"####"
//		}
//		reference:"13a5e52976337ab210903cd04872588e1b21fb72bc183e91aa25c494b8138551",
//		oldestUsedEncryptedVersion:"0.2",
//		accessDate:"Wed, 13 February 2008 14:25:12 UTC",
//		creationDate:"Wed, 14 March 2007 13:53:11 UTC",
//		version:"0.2",
//		updatedDate:"Tue, 17 April 2007 17:17:52 UTC",
//		data:"0/BjzyY6jeh71h...pAw2++NEyylGhMC5C5f5m8pBApYziN84s4O3JQ3khW/1UttQl4="
//	}
					$record = new record();

					$recordList = $record->GetList(array(array("reference", "=", $parameters["parameters"]["reference"])));
					$currentRecord = $recordList[0];
					$currentRecordVersions = $currentRecord->GetRecordversionList();
					$currentVersion = $currentRecordVersions[0];
				
					if ($gammaClient == true) {
						$result["currentVersion"] = $currentVersion->reference;
						$currentVersionInfo = array();
						$currentVersionInfo['reference'] =			$currentVersion->reference;
						$currentVersionInfo['data'] =			$currentVersion->data;
						$currentVersionInfo['header'] =			$currentVersion->data;
						$currentVersionInfo['version'] =			$currentVersion->version;
						$currentVersionInfo['updateDate'] =			$currentVersion->update_date;
						$currentVersionInfo['creationDate'] =			$currentVersion->creation_date;
						$currentVersionInfo['accessDate'] =			$currentVersion->access_date;
						$result["versions"] = array();
						$result["versions"][$currentVersion->reference] = $currentVersionInfo;
					} else {
						$result["currentVersion"] = array();
						$result["currentVersion"]["reference"] =	$currentVersion->reference;
						$result["currentVersion"]["data"] =			$currentVersion->data;
						$result["currentVersion"]["header"] =		$currentVersion->header;
						$result["currentVersion"]["version"] =		$currentVersion->version;
						$result["currentVersion"]["creationDate"] =	$currentVersion->creation_date;
						$result["currentVersion"]["updateDate"] =	$currentVersion->update_date;
						$result["currentVersion"]["accessDate"] =	$currentVersion->access_date;
					}

					$result["reference"] =		$currentRecord->reference;
					$result["data"] =			$currentRecord->data;
					$result["version"] =		$currentRecord->version;
					$result["creationDate"] =	$currentRecord->creation_date;
					$result["updateDate"] =		$currentRecord->update_date;
					$result["accessDate"] =		$currentRecord->access_date;
					$result["oldestUsedEncryptedVersion"] =	"---";

				//=============================================================
				} else if ($message == "saveChanges") {
//    "parameters":{
//        "message":"saveChanges",
//        "srpSharedSecret":"733b3bf26b71e1055abbfcfe903f1ea07ead0e7650738f149f2aa21a00643899",
//        "parameters":{
//            "records":{
//                "updated":[
//{"currentContactVersion":{"previousVersionKey":"####","reference":"b2ddd708d5c5523cb3321c4fe1730683fdac2c415bc053459aa00f3ccc8df995","data":"FLMNRGfvVFfJic6cpDM+YDns","version":"0.3"},"record":{"reference":"aa516da79b20ff04ccbd8624ab38daf51081fc35f0dd313baf70d4130c93c556","data":"b3/l1GbAc7XNQK4a4jObd5b/G0KIFBEspt/Su0OtpraFXXK2dCSirForwCKonF4CZK9Sn6QPjk3MmmGVIMVHOE0urDzvXmqjSv1iXnp91RX8j6WgH5daRLPPbecNVN2IUEaghQYnbVO2KcNwcUFXg3lageqgmNBG9Z8=","version":"0.3"}}
//                ],
//                "deleted":[]
//            },
//            "contacts":{
//                "updated":[],
//                "deleted":[]
//            },
//            "user":{
//                "header":"{
//                    \"records\":{
//                        \"index\":{
//                             \"b4d0158b049262ba...0d8f291b831bedcbdbdf8\":\"0\"
//                        },
//                        \"data\":\"fRJOT4FdOcGB/D...h7k+pDX0ydAsT4T5ypwK3UjQOS0=\"
//                    },
//                    \"contacts\":{
//                        \"index\":{
//                            \"1cc26d7de0d8b56af1515...39954438b0e84\":\"1\"
//                        },
//                        \"data\":\"FsjXLalsFEjq8o1zKA...IG11Tsq3xduMPV8B+YFSnRu22f2ridPmA==\"
//                    },
//                    \"directLogins\":{
//                        \"index\":{
//                            \"49ced0d7c219c6e2f3c4...ffa7d5640fbfd7ba10674\":\"0\"
//                        },
//                        \"data\":\"yMqPTZ...v+P3IK1XTZDWmFg==\"
//                    },
//                    \"preferences\":{
//                        \"data\":\"AdSuZ...PCBk\"
//                    },
//                    \"oneTimePasswords\":{
//                        \"data\":\"fR8l...qc3oC7\"
//                    },
//                    \"version\":\"0.1\"
//                }",
//                "statistics":"XKN19EHBy...Zs3E4",
//                "version":"0.3"
//            }
//        }
//    }

					$user = new user();
					$user = $user->Get($_SESSION["userId"]);
					updateUserData($parameters["parameters"]["user"], $user);
					$user->Save();

/*
					$recordParameterList = $parameters["parameters"]["contacts"]["updated"];
					$c = count($recordParameterList);
					for ($i=0; $i<$c; $i++) {
						$recordList = $user->GetRecordList(array(array("reference", "=", $recordParameterList[$i]["record"]["reference"])));
						$currentRecord = $recordList[0];
						if (!is_null($currentRecord)) {
							$currentRecordVersions = $currentRecord->GetRecordversionList();
							$currentVersion = $currentRecordVersions[0];
							updateRecordData($recordParameterList[$i], $currentRecord, $currentVersion);
							$currentRecord->Save();
							$currentVersion->Save();
						} else {
							$record = new record();
							$recordVersion = new recordversion();
							updateRecordData($recordParameterList[$i], $record, $recordVersion);

						updateRecordData($recordParameterList[$i], $currentRecord, $currentVersion);


						$currentRecord->Save();
						$currentVersion->Save();
					}

					$recordReferenceList = $parameters["parameters"]["contacts"]["deleted"];
					$recordList = array();
					$c = count($recordReferenceList);
					for ($i=0; $i<$c; $i++) {
						array_push($recordList, array("reference", "=", $recordReferenceList[$i]));
					}

					$record = new record();
					$record->DeleteList($recordList, true);
*/
					
					$record = new record();
					$recordVersion = new recordversion();
					$recordParameterList = $parameters["parameters"]["records"]["updated"];
					$c = count($recordParameterList);
					for ($i=0; $i<$c; $i++) {
						$recordList = $user->GetRecordList(array(array("reference", "=", $recordParameterList[$i]["record"]["reference"])));
						$currentRecord = $recordList[0];
						if (!is_null($currentRecord)) {
							$currentRecordVersions = $currentRecord->GetRecordversionList();
							$currentVersion = $currentRecordVersions[0];

							updateRecordData($recordParameterList[$i], $currentRecord, $currentVersion);


							$currentRecord->Save();
							$currentVersion->Save();
						} else {
							updateRecordData($recordParameterList[$i], $record, $recordVersion);

							$record->SaveNew();
							$recordVersion->SaveNew();

							$record->AddRecordversion($recordVersion);
							$user->AddRecord($record);
							
							$record->Save();
							$recordVersion->Save();
						}
					}

					$recordReferenceList = $parameters["parameters"]["records"]["deleted"];
					$recordList = array();
					$c = count($recordReferenceList);
					for ($i=0; $i<$c; $i++) {
						array_push($recordList, array("reference", "=", $recordReferenceList[$i]));
					}

					$record = new record();
					$record->DeleteList($recordList, true);
					
					$result["lock"] = $user->lock;
					$result["result"] = "done";

				} else if ($message == "updateData") {
//{
//	"message":"updateData",
//	"srpSharedSecret":"4e4aadb1d64513ec4dd42f5e8d5b2d4363de75e4424b6bcf178c9d6a246356c5",
//	"parameters":{
//		"records":[
//			{
//				"record":{
//					"reference":"740009737139a189cfa2b1019a6271aaa39467b59e259706564b642ff3838d50",
//					"data":"8hgR0Z+JDrUa812polDJ....JnZUKXNEqKI",
//					"version":"0.3"
//				},
//				"currentRecordVersion":{
//					"reference":"b1d82aeb9a0c4f6584bea68ba80839f43dd6ede79791549e29a1860554b144ee",
//					"recordReference":"740009737139a189cfa2b1019a6271aaa39467b59e259706564b642ff3838d50",
//					"data":"2d/UgKxxV+kBPV9GRUE.....VGonDoW0tqefxOJo=",
//					"version":"0.3",
//					"previousVersion":"55904195249037394316d3be3f5e78f08073170103bf0e7ab49a911c159cb0be",
//					"previousVersionKey":"YWiaZeMIVHaIl96OWW+2e8....6d6nHbn6cr2NA/dbQRuC2w=="
//				}
//			}
//		],
//		"user":{
//			"header":"{\"rec.....sion\":\"0.1\"}",
//			"statistics":"tt3uU9hWBy8rNnMckgCnxMJh",
//			"version":"0.3",
//			"lock":"----"
//		}
//	}
//}

					$user = new user();
					$user = $user->Get($_SESSION["userId"]);
					updateUserData($parameters["parameters"]["user"], $user);
					$user->Save();

					$recordParameterList = $parameters["parameters"]["records"];
					$c = count($recordParameterList);
					for ($i=0; $i<$c; $i++) {
						$recordList = $user->GetRecordList(array(array("reference", "=", $recordParameterList[$i]["record"]["reference"])));
						$currentRecord = $recordList[0];
						$currentRecordVersions = $currentRecord->GetRecordversionList();
						$currentVersion = $currentRecordVersions[0];

						updateRecordData($recordParameterList[$i], $currentRecord, $currentVersion);


						$currentRecord->Save();
						$currentVersion->Save();
					}

					
					$result["lock"] = $user->lock;
					$result["result"] = "done";

				//=============================================================
				} else if ($message == "deleteRecords") {
//{
//	"message":"deleteRecords",
//	"srpSharedSecret":"4a64982f7ee366954ec50b9efea62a902a097ef111410c2aa7c4d5343bd1cdd1",
//	"parameters":{
//		"recordReferences":["46494c81d10b80ab190d41e6806ef63869cfcc7a0ab8fe98cc3f93de4729bb9a"],
//		"user":{
//			"header":"{\"rec...rsion\":\"0.1\"}",
//			"statistics":"44kOOda0xYZjbcugJBdagBQx",
//			"version":"0.3",
//			"lock":"----"
//		}
//	}
//}
					$user = new user();
					$user = $user->Get($_SESSION["userId"]);

					$recordReferenceList = $parameters["parameters"]["recordReferences"];
					$recordList = array();
					$c = count($recordReferenceList);
					for ($i=0; $i<$c; $i++) {
						array_push($recordList, array("reference", "=", $recordReferenceList[$i]));
					}

					$record = new record();
					$record->DeleteList($recordList, true);

					updateUserData($parameters["parameters"]["user"], $user);
					$user->Save();

					$result["recordList"] = $recordList;
					$result["lock"] = $user->lock;
					$result["result"] = "done";

				//=============================================================
				} else if ($message == "deleteUser") {
//{"message":"deleteUser", "srpSharedSecret":"e8e4ca6544dca49c95b3647d8358ad54c317048b74d2ac187ac25f719c9bac58", "parameters":{}}
					$user = new user();
					$user->Get($_SESSION["userId"]);
					$user->Delete(true);

					$result["result"] = "ok";

				//=============================================================
				} else if ($message == "addNewOneTimePassword") {
//{
//	"message":"addNewOneTimePassword",
//	"srpSharedSecret":"96fee4af06c09ce954fe7a9f87970e943449186bebf70bac0af1d6ebb818dabb",
//	"parameters":{
//		"user":{
//			"header":"{\"records\":{\"index\":{\"419ea6....rsion\":\"0.1\"}",
//			"statistics":"rrlwNbDt83rpWT4S72upiVsC",
//			"version":"0.3",
//			"lock":"----"
//		},
//		"oneTimePassword":{
//			"reference":"29e26f3a2aae61fe5cf58c45296c6df4f3dceafe067ea550b455be345f44123c",
//			"key":"afb848208758361a96a298b9db08995cf036011747809357a90645bc93fdfa03",
//			"keyChecksum":"d1599ae443b5a566bfd93c0aeec4c81b42c0506ee09874dae050449580bb3486",
//			"data":"hsyY8DHksgR52x6c4j7XAtIUeY.....dxsr3XWt7CbGg==",
//			"version":"0.3"
//		}
//	}
//}

					fixOTPStatusTable();

					$user = new user();
					$user = $user->Get($_SESSION["userId"]);

					$otp = new onetimepassword();
					updateOTP($parameters["parameters"]["oneTimePassword"], $otp, "ACTIVE");
					$user->AddOnetimepassword($otp);

					updateUserData($parameters["parameters"]["user"], $user);
					$user->Save();

					$result["lock"] = $user->lock;
					$result["result"] = "done";

				//=============================================================
				} else if ($message == "updateOneTimePasswords") {
//{
//	"message":"updateOneTimePasswords",
//	"srpSharedSecret":"c78f8ed099ea421f4dd0a4e02dbaf1f7da925f0088188d99399874ff064a3d27",
//	"parameters":{
//		"user":{
//			"header":"{\"reco...sion\":\"0.1\"}",
//			"statistics":"UeRq75RZHzDC7elzrh/+OB5d",
//			"version":"0.3",
//			"lock":"----"
//		},
//		"oneTimePasswords":["f5f44c232f239efe48ab81a6236deea1a840d52946f7d4d782dad52b4c5359ce"]
//	}
//}

					$user = new user();
					$user = $user->Get($_SESSION["userId"]);

					$validOtpReferences = $parameters["parameters"]["oneTimePasswords"];
					
					$otpList = $user->GetOnetimepasswordList();
					$c = count($otpList);
					for ($i=0; $i<$c; $i++) {
						$currentOtp = $otpList[$i];
						if (arrayContainsValue($validOtpReferences, $currentOtp->reference) == false) {
							$currentOtp->Delete();
						}
					}
					
					updateUserData($parameters["parameters"]["user"], $user);
					$user->Save();

					$result["result"] = $user->lock;

				//=============================================================
				} else if ($message == "getOneTimePasswordsDetails") {

				//=============================================================
				} else if ($message == "getLoginHistory") {
					$result["result"] = array();

				//=============================================================
				} else if ($message == "upgradeUserCredentials") {
//{
//	"message":"upgradeUserCredentials",
//	"srpSharedSecret":"f1c25322e1478c8fb26063e9eef2f6fc25e0460065a31cb718f80bcff8f8a735",
//	"parameters":{
//		"user":{
//			"header":"{\"reco...sion\":\"0.1\"}",
//			"statistics":"s72Xva+w7CLgH+ihwqwXUbyu",
//			"version":"0.3",
//			"lock":"----"
//		},
//		"credentials":{
//			"C":"57d15a8afbc1ae08103bd991d387ddfd8d26824276476fe709d754f098b6c26d",
//			"s":"d6735fc0486f391c4f3c947928f9e61a2418e7bed2bc9b25bb43f93acc52f636",
//			"v":"540c2ebbf941a481b6b2c9026c07fb46e8202e4408ed96864a696deb622baece",
//			"version":"0.2"
//		},
//		"oneTimePasswords":{
//			"923cdc61c4b877b263236124c44d69b459d240453a461cce8ddf7518b423ca94": "1HD6Ta0xsifEDhDwE....9WDK6tvrS6w==",
//			"fb1573cb9497652a81688a099a524fb116e604c6fbc191cf33406eb8438efa5f": "CocN0cSxLmMRdgNF9....o3xhGUEY68Q=="
//		}
//	}
//}

					$user = new user();
					$user->Get($_SESSION["userId"]);

					$otp = new onetimepassword();
					
					updateUserCredentials($parameters["parameters"]["credentials"], $user);
					updateUserData($parameters["parameters"]["user"], $user);

					$otpList = $parameters["parameters"]["oneTimePasswords"];
					foreach($otpList as $otpReference=>$otpData) {
						$otpList = $otp->GetList(array(array("reference", "=", $otpReference)));
						$currentOtp = $otpList[0];
						$currentOtp->data = $otpData;
						$currentOtp->Save();
					}

					$user->Save();

					$result["lock"] = $user->lock;
					$result["result"] = "done";
					
				//=============================================================
				} else if ($message == "echo") {
					$result["result"] = $parameters;
				}
				
				//=============================================================
			} else if (isset($_SESSION['K'])) {
				$result["error"] = "Wrong shared secret!";
			} else {
				$result["results"] = "EXCEPTION";
				$result["message"] = "Trying to communicate without an active connection";
			}
			break;

		case "logout":
			session_destroy();
			break;

		default:
			$result["result"] = $parameters;
			break;
	}

	session_write_close();
	
	if ($gammaClient == true) {
		$res["result"] = $result;
		echo(json_encode($res));
	} else {
		echo(json_encode($result));
	}
?>
