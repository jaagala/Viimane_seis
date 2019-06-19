<?php
require ("../../../config.php");
header('Content-Type: text/html; charset=utf-8');
$database = "if18_andri_ka_1";
session_start();
function signin($email, $password){
	$notice = "";
	$mysqli = new mysqli($GLOBALS["serverHost"], $GLOBALS["serverUsername"], $GLOBALS["serverPassword"], $GLOBALS["database"]);
	$stmt = $mysqli->prepare("SELECT id, firstname, email, password FROM kasutajad WHERE email=?");
	echo $mysqli->error;
	$stmt->bind_param("s", $email);
	$stmt->bind_result($idFromDb, $firstNameFromDb, $emailFromDb, $passwordFromDb);
	if($stmt->execute()){
	  if($stmt->fetch()){
		  $stmt -> close();
			$stmt= $mysqli->prepare ("UPDATE kasutajad SET counter= counter + 1 WHERE id=$idFromDb");
			$stmt ->execute();
		  if(password_verify($password,$passwordFromDb)){
				$notice = "Logisite sisse";
				$_SESSION["userId"] = $idFromDb;
				$_SESSION["userName"] = $firstNameFromDb;
				$_SESSION["userEmail"] = $emailFromDb;
				$_SESSION["userCounter"] = $counterFromDb;
				header("Location: upload.php");
				exit();
		  } else {
		    $notice = "Vale salasõna";
		  }
	  } else {
	    $notice = "Sellist kasutajat(" .$email .") ei leitud";
	  }
	} else {
	  $notice = "Sisenemisel tekkis viga" .$stmt->error;
	}
	$stmt->close();
	$mysqli->close();
	return $notice;
  }

function signup($firstName, $lastName, $email, $password){
	$notice = "";
	$mysqli = new mysqli($GLOBALS["serverHost"], $GLOBALS["serverUsername"], $GLOBALS["serverPassword"], $GLOBALS["database"]);
	$stmt = $mysqli->prepare("SELECT id FROM kasutajad WHERE email=?");
	echo $mysqli->error;
	$stmt->bind_param("s",$email);
	$stmt->execute();
	if($stmt->fetch()){
		$notice = "Sellise kasutajatunnusega (" .$email .") kasutaja on juba olemas! Uut kasutajat ei salvestatud!";
	} else {
			$stmt->close();
			$stmt = $mysqli->prepare("INSERT INTO kasutajad (firstname, lastname, email, counter, password) VALUES(?,?,?,1, ?)");
    	echo $mysqli->error;
	    $options = ["cost" => 12, "salt" => substr(sha1(rand()), 0, 22)];
	    $pwdhash = password_hash($password, PASSWORD_BCRYPT, $options);
	    $stmt->bind_param("ssss", $firstName, $lastName, $email, $pwdhash);
	    if($stmt->execute()){
		  	$notice = "Kasutaja edukalt loodud";
	    } else {
	      $notice = "error" .$stmt->error;
	    }
	}
	return $notice;
	$stmt ->close();
	$mysqli->close();
  }

function upload($description, $dateFrom, $dateTo){
	global $tempFileName;
	$notice ="";
	$id = $_SESSION["userId"];
	$notice = "";
	$mysqli = new mysqli($GLOBALS["serverHost"], $GLOBALS["serverUsername"], $GLOBALS["serverPassword"], $GLOBALS["database"]);
	$stmt = $mysqli ->prepare("SELECT failinimi FROM failid WHERE failinimi=?");
	echo $mysqli->error;
	$stmt -> bind_param("s", $description);
	$stmt->execute();
	if($stmt->fetch()){
		echo "<script language='JavaScript' type='text/javascript'> alert('Sellise nimega fail on juba olemas');</script>";
	}	else{
		$stmt -> close();
		$stmt = $mysqli->prepare("INSERT INTO failid (failinimi, algus, lopp, kasutaja_id) VALUES(?,?,?,?)");
		$stmt->bind_param("sssi", $description, $dateFrom, $dateTo, $id);
		echo $mysqli->error;
		if($dateTo < $dateFrom){
			echo "<script language='JavaScript' type='text/javascript'> alert('Lõpukuupäev ei saa olla varem!');</script>";
		} else {
			$stmt->execute();
			echo $stmt->error;
			echo "<script language='JavaScript' type='text/javascript'> alert('Fail üleslaetud!');</script>";
		}
		}
		$stmt ->close();
		$mysqli->close();
		return $notice;
}

function showupload($description, $dateFrom, $dateTo){
	$id = $_SESSION["userId"];
	$mysqli = new mysqli($GLOBALS["serverHost"], $GLOBALS["serverUsername"], $GLOBALS["serverPassword"], $GLOBALS["database"]);
	if(isset($_POST["sort"])){
		$sort = $_POST["sort"];
		$criteria = $_POST["subject"];
		if(isset($_POST["searchBox"])){
			$search = $_POST["search"];
			$stmt = $mysqli->prepare("SELECT id, failinimi, algus, lopp FROM failid WHERE (kasutaja_id = $id AND failinimi LIKE '%$search%') ORDER BY $criteria $sort ");
		} else {
			$stmt = $mysqli->prepare("SELECT id, failinimi, algus, lopp FROM failid WHERE kasutaja_id = $id ORDER BY $criteria $sort ");
		}
	} else {
		$sort = "ASC";
		$criteria = "lopp";
		if(isset($_POST["searchBox"])){
			$search = $_POST["searchBox"];
			$stmt = $mysqli->prepare("SELECT id, failinimi, algus, lopp FROM failid WHERE (kasutaja_id = $id AND failinimi LIKE '%$search%') ORDER BY $criteria $sort ");
		} else {
			$stmt = $mysqli->prepare("SELECT id, failinimi, algus, lopp FROM failid WHERE kasutaja_id = $id ORDER BY $criteria $sort ");
		}
	}
	$source = "uploads/".$description;
	echo $mysqli->error;
	$stmt->bind_result($photoId, $description, $dateFrom, $dateTo);
	$stmt -> execute();
	echo '<div class="photoRow" id="photoRow"> ';
	echo "\n";
	echo '<div class="photoColumn" id="photoColumn"> ';
	echo "\n";
	echo "<style> table tr,th,td { color: black;  width:10%;font-family:Arial; background-color: #ffffff; border-bottom: 2px solid black;} \n";
	echo "tr{ margin-left: 20px;} \n";
	echo "table{width:150%;text-align:center; font-size:18px; border-collapse:collapse; border: 3px solid black;} \n";
	echo "th{background-color:  #FFA500 ; color: white;  border-bottom: 2px solid black;} </style>";
	echo "<table>";
	echo "<tr>";
	echo "<th> Fail </th>";
	echo "<th> Kirjeldus </th>";
	echo "<th> Algus </th>";
	echo "<th> Lõpp</th>";
	echo "<th> Aegumine </th>";
	echo "<th> Kustuta </th>";
	echo "</tr>";
	while($stmt->fetch()){
			$newFrom = date("d/m/Y", strtotime($dateFrom));
			$newTo2 = date("d/m/Y", strtotime($dateTo));
			$fileExt = pathinfo($description)['extension'];
			$confirm = "Kas te olete kindel?";
			if($fileExt == "pdf"){
				$source = '<a target="_blank" href="uploads/' .$description .'" type="application/pdf" onclick="setTimeout(waitFunc, 100)"><img border="0" alt=' .$description .' src="pics/pdf.png" width="50px" heigth="25px" ></a>';
				echo "<script language='JavaScript' type='text/javascript' > </script>";
			} else {
				setlocale(LC_ALL, 'en_US.UTF-8');
				$source = '<img data-fn=' .$description .' class="photo" src="uploads/' .$description .'" data-id="' .$photoId .'" alt="' .pathinfo($description)['filename'] .'" style="height: 5vh; width: 10vh;">';
			}
			$delete = "<a onclick='return confirmDelete()' href=deleteThisFile.php?id=" .$photoId ."&file=".$description ." class='deleteBtn' ><img border='0' alt='Kustuta' src='pics/delete_img.png' width='25px' height='25px'></a>";
			$dateNow = date("Y-m-d");
			$dateNow = date_create($dateNow);
			$dateEnd = date_create($dateTo);
			$dateDiff = date_diff($dateNow, $dateEnd);
			$sentence1 = "<td > <p id='daysRemaining' style='color: red;' >" .$dateDiff->format('%a päeva') ."</p></td>";
			$sentence2 = "<td > <p id='daysRemaining' style='color: orange;' >" .$dateDiff->format('%a päeva') ."</p></td>";
			$sentence3 = "<td > <p id='daysRemaining' >" .$dateDiff->format('%a päeva') ."</p></td>";
			$sentence4 = "<td > <p id='daysRemaining' style='color: red;' >" .$dateDiff->format('%a päev') ."</p></td>";
			$sentence5 = "<td > <p id='daysRemaining' style='color: red;' >" .$dateDiff->format('%r%a päeva') ."</p></td>";
			$hiddenData = "<input type='hidden' name='hiddenId' id='hiddenId' value =" .$photoId ."><input type='hidden' name='hiddenExt' id='hiddenExt' value=" .$fileExt ."><input type='hidden' name='hiddenName' value=" .$description .">";
			echo "<form action='myfiles.php' method='post' name='update' id='photo" .$photoId ."'>";
			echo "<tr>";
			echo "<td> " .$source .$hiddenData ."</td>";
			setlocale(LC_ALL, 'en_US.UTF-8');
			echo "<td> <input name='description' type='data' value='".pathinfo($description)['filename'] ."' class='dates'></td>";
			echo "<td> <input onClick='date(this.id)' name='dateFrom' type='date' value=" .$dateFrom ." id=" .$photoId ." class='dates'></td>";
			echo "<td> <input onClick='date(this.id)' name='dateTo' type='date' value=" .$dateTo ." id=" .$photoId ." class='dates'></td>";
			if($dateDiff->format('%r%a') < 0){
				echo $sentence5;
			} else if($dateDiff->format('%a') ==1){
				echo $sentence4;
			} elseif($dateDiff->format('%a') <= 7){
					echo $sentence1;
			} elseif($dateDiff->format('%a') <= 14) {
					echo $sentence2;
			} else {
					echo $sentence3;
			}
			echo "<td>  <input name='update' type='hidden' value='Redigeeri' id=change" .$photoId ." onkeydown='keyCode(event)' />$delete</td>";
			echo"</tr>";
			echo "</form>";
			echo '</div>';
	}
	echo "</table>";
	echo "\n";
	echo '</div>';
	echo "\n";
	if(empty($html)){
		$html = "<p>Kahjuks pilte pole!</p> \n";
	}
}

function deleteImage($fileToDelete){
	$mysqli = new mysqli($GLOBALS["serverHost"], $GLOBALS["serverUsername"], $GLOBALS["serverPassword"], $GLOBALS["database"]);
	$stmt = $mysqli->prepare("DELETE FROM failid WHERE failinimi= '$fileToDelete'");
	echo $mysqli->error;
	if($stmt -> execute()){
		echo "fail kustutati.";
	}else{
		echo "faili ei kustutatud.";
	}
	$stmt ->close();
	$mysqli->close();
}

if(isset($_POST['update'])){
	update();
}

function update(){
	$updateFrom = $_POST['dateFrom'];
	$updateTo = $_POST['dateTo'];
	$hiddenExt = $_POST['hiddenExt'];
	$toUpdate = $_POST['hiddenId'];
	$updateName = $_POST['description']  .".".$hiddenExt;
	$hiddenName = $_POST['hiddenName'];
	$mysqli = new mysqli($GLOBALS["serverHost"], $GLOBALS["serverUsername"], $GLOBALS["serverPassword"], $GLOBALS["database"]);
	if($_POST['description']==""){
		echo "<script language='JavaScript' type='text/javascript' > alert('Faili nimi ei saa tühi olla.')</script>";
	}else{
		$stmt = $mysqli ->prepare("SELECT failinimi FROM failid WHERE failinimi=?");
		echo $mysqli->error;
		$stmt -> bind_param("s", $updateName);
		$stmt->execute();
		if($stmt->fetch()){
			if($updateName == $_POST['description']  .".".$hiddenExt){
				$stmt -> close();
				if($updateFrom > $updateTo){
					echo "<script language='JavaScript' type='text/javascript' > alert('Lõpukuupäev ei saa olla alguse kuupäevast varem.')</script>";
				}else{
					$stmt = $mysqli->prepare("UPDATE failid SET algus = '$updateFrom', lopp = '$updateTo' WHERE id = $toUpdate ");
					echo $mysqli->error;
					$stmt->execute();
					$stmt->close();
					$mysqli->close();
					echo "<script language='JavaScript' type='text/javascript' > alert('Muudatused viidi läbi edukalt.')</script>";
				}
			} else {
				echo "<script language='JavaScript' type='text/javascript' > alert('Sellise nimega fail on juba olemas.')</script>";
			}
		} else {
			$stmt -> close();
			if($updateFrom > $updateTo){
				echo "<script language='JavaScript' type='text/javascript' > alert('Lõpukuupäev ei saa olla alguse kuupäevast varem.')</script>";
			}else{
				$stmt = $mysqli->prepare("UPDATE failid SET failinimi = '$updateName', algus = '$updateFrom', lopp = '$updateTo' WHERE id = $toUpdate ");
				echo $mysqli->error;
				$stmt->execute();
				$stmt->close();
				$mysqli->close();
				$oldSrc = "uploads/" .$hiddenName;
				$newSrc = "uploads/" .$updateName;
				rename($oldSrc, $newSrc);
				echo "<script language='JavaScript' type='text/javascript' > alert('Muudatused viidi läbi edukalt.')</script>";
			}
		}
	}
}

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}
