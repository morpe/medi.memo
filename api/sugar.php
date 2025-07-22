<?php

function mese($ts)
{
	$i = date("n", $ts);

	$mesi = array(
		1 => 'gennaio',
		2 => 'febbraio',
		3 => 'marzo',
		4 => 'aprile',
		5 => 'maggio',
		6 => 'giugno',
		7 => 'luglio',
		8 => 'agosto',
		9 => 'settembre',
		10 => 'ottobre',
		11 => 'novembre',
		12 => 'dicembre'
	);

	return $mesi[$i];
}

function giorni($ts)
{

	$i = date("w", $ts);

	//if( $i == 28 ) echo "$i $ts";

	$giorni = array(
		1 => 'lunedì',
		2 => 'martedì',
		3 => 'mercoledì',
		4 => 'giovedì',
		5 => 'venerdì',
		6 => 'sabato',
		0 => 'domenica'
	);

	return $giorni[$i];
}

function loadBabboLog()
{
	$file = file("babbo.log");
	$theLog = array();
	foreach ($file as $id => $row) {
		$boom = explode("\t", $row);
		//$boomNote = explode("|",$row);
		$sug = $boom[1]; // valore zucchero

		$sugNote = explode("|", $sug);

		$sug = $sugNote[0];
		$note = $sugNote[1];

		//var_dump($boom[0]);


		$dt = (object) date_parse_from_format("d/m/Y H:i",$boom[0]); // data
		//var_dump($boom);

		$Y = $dt->year;
		$M = $dt->month;
		$D = $dt->day;
		$h = $dt->hour;
		$m = $dt->minute;
		
		$ts = mktime($h,$m,0,$M,$D,$Y);
		$mese = mese($ts);
		$giorno = giorni($ts);

		$dt->mese = $mese;
		$dt->giorno = $giorno;
		
		$theLog[$ts]["ts"] = $ts;
		$theLog[$ts]["sugar"] = $sug;
		$theLog[$ts]["note"] = $note;
		$theLog[$ts]["date_str"] = $boom[0];
		$theLog[$ts]["date_arr"] = (array) $dt;
		$theLog[$ts]["raw"] = $row;
		//var_dump($theLog[$ts]);
	}
	return $theLog;
}

$log = loadBabboLog();

function buildDB($log)
{
	if ( file_exists("sugarDb.log")) {
		//echo "no new db!";
	} else {
		$cc = 0;
		foreach ($log as $i => $arr) {

			$ts = $arr["ts"];
			$out["ts"] = $ts;
			$out["sugar"] = $arr['sugar'];
			$out["note"] = str_ireplace("\n", "", $arr['note']);

			$dtArr = (object) $arr['date_arr'];
			$time = "$dtArr->hour:$dtArr->minute";
			$date = "$dtArr->giorno $dtArr->day $dtArr->mese $dtArr->year";

			$out["time"] = $time;
			$out["date"] = $date;

			$json = json_encode($out);
			$bin = bin2hex($json) . "\n";

			//var_dump([$cc,$json,$bin]);

			$cc++;

			file_put_contents("sugarDb.log", $bin, FILE_APPEND);
		}

		//echo $cc;
	}
}

function getLog()
{
	if (file_exists("sugarDb.log")) {
		$file = file("sugarDb.log");

		foreach ($file as $i => $bin) {

			$bin = str_ireplace("\n", "", $bin); // rimuove \n

			$json = hex2bin($bin);
			$obj = json_decode($json);

			//var_dump($obj);
			
			$ts = $obj->ts;
			$out[$ts]["ts"] = $ts;
			$out[$ts]["sugar"] = $obj->sugar;
			$out[$ts]["note"] = $obj->note;
			
			$time = date("H:i", $ts);
			$out[$ts]["time"] = $time;

			$out[$ts]["date"] = $obj->date;

			if( $obj->sugar >= "140" ){
				$x = $obj->sugar - 140;
				$out[$ts]["interval"] = "+$x";  
			}
			else if( $obj->sugar <= "70")
			{
				$x = 70 - $obj->sugar;
				$out[$ts]["interval"] = "-$x";  
			}
			else{
				$out[$ts]["interval"] = ""; 
			}

		}

		
		krsort($out); 
		
		//var_dump($out);

		foreach( $out as $ts => $arr ){
			$noKey[] = $arr;
		}

		return $noKey;
	} else {
		return false;
	}
}



function get($v)
{
	if (isset($_GET[$v])) {
		return true;
	} else {
		return false;
	}
}


buildDB($log);

if( get("s") ){
	$out = getLog();
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($out);
}

$phpIn = file_get_contents('php://input');
$phpObj = json_decode($phpIn);

$test = '{"call":"add-sugar","note":"","sugar":"90","ore":11,"min":39,"sec":16,"giorno":13,"mese":4,"anno":2022}';

function addToDB($in){
	
	$obj = json_decode($in);

	$H = $obj->ore;
	$m = $obj->min;
	$s = $obj->sec;

	$D = $obj->giorno;
	$M = $obj->mese;
	$Y = $obj->anno;

	$ts = mktime($H,$m,$s,$M,$D,$Y);

	$mese = mese($ts);
	$giorno = giorni($ts);
	
	$out["ts"] = $ts;
	$out['sugar'] = $obj->sugar;
	$out['note'] = $obj->note;

	// 01/02/2022 04:05
	$dateObj = "$D/$M/$Y $H:$m:$s";
	$dateObj = (object) date_parse_from_format("d/m/Y H:i:s",$dateObj);

	$out['time'] = "$H:$m";
	$out['date'] = "$giorno $D $mese $Y";

	$json = json_encode($out);
	$hex = bin2hex($json)."\n";

	$fpc = file_put_contents("sugarDb.log",$hex,FILE_APPEND);

	if( is_int($fpc) ){
		$r["success"] = true;
		$r['ts'] = $ts;
		$r['msg'] = "good update!";
	}
	else{
		$r["success"] = false;
		$r['ts'] = $ts;
		$r['msg'] = "DHO!!";
	}

	return $r;
	
}

/*
export class PostCall {
	success: boolean = false;
	ts: string = "";
	msg: string = "welcome!";
}
*/

function undo(){
	$file = file("sugarDb.log");
	$last = count($file) -1;
	unset( $file[$last] );
	$fpc = file_put_contents("sugarDb.log",$file);

	if( is_int($fpc) ){
		$r["success"] = true;
		$r['ts'] = "";
		$r['msg'] = "good undo update!";
	}
	else{
		$r["success"] = false;
		$r['ts'] = "";
		$r['msg'] = "DHO!!";
	}

	return $r;


}



if( isset( $phpObj->call ) ){
	if( $phpObj->call == "dummy" ){
		echo $phpIn;
	}

	if( $phpObj->call == "add-sugar" ){
		$out = addToDB($phpIn);
		echo json_encode($out);
	}

	if( $phpObj->call == "undo" ){
		$out = undo();
		echo json_encode($out);
	}
}




?>