<?php

ini_set('display_errors', '0'); // Disabilita la visualizzazione degli errori
ini_set('log_errors', '1');     // Abilita il logging degli errori
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // Escludi avvisi e notifiche


date_default_timezone_set("Europe/Rome");

function drgMD5($drg = false)
{
	$drgs['aulin'] = md5("aulin");
	$drgs['accord'] = md5("accord");
	$drgs['novalgina'] = md5("novalgina");
	$drgs['tachi1000'] = md5("Tachi1000");
	$drgs['actigrip'] = md5("Actigrip");
	$drgs['prednisone'] = md5("Prednisone");
	$drgs['rinvoq'] = md5("Rinvoq");
	$drgs['xeljanz'] = md5("xeljanz");
	$drgs['HU'] = md5("HU");
	$drgs['note'] = md5("note");
	$drgs['custom'] = md5("custom");

	return $drgs[$drg];
}


function loadDR()
{
	if (file_exists("dr.log")) {

		$out = file_get_contents("dr.log");

		$arr = explode("\n", $out);
		$arr = array_unique($arr);

		$out = [];
		$c = 0;
		foreach ($arr as $x => $line) {
			$line = str_ireplace(md5("aulin"), "aulin", $line);
			$line = str_ireplace(md5("accord"), "accord", $line);
			$line = str_ireplace(md5("novalgina"), "novalgina", $line);
			$line = str_ireplace(md5("Tachi1000"), "tachi1000", $line);
			$line = str_ireplace(md5("Actigrip"), "actigrip", $line);
			$line = str_ireplace(md5("Prednisone"), "prednisone", $line);
			$line = str_ireplace(md5("Rinvoq"), "rinvoq", $line);
			$line = str_ireplace(md5("xeljanz"), "xeljanz", $line);
			$line = str_ireplace(md5("note"), "note", $line);
			$line = str_ireplace(md5("custom"), "custom", $line);


			$line = explode("/", $line);

			if (isset($line[0]) and  isset($line[1])) {

				$ts = $line[0];

				$out[$ts]["ts"] = $line[0];
				//$out[$ts]["date"] = date("Y/m/d H:i:s",$ts); serve solo per vedere se l'ordine è giusto

				if ($line[1] == "custom") {
					$out[$ts]["drug"] = strtolower(hex2bin($line[3]));
				} else {
					$out[$ts]["drug"] = $line[1];
				}

				if (isset($line[2])) {
					$out[$ts]["note"] = $line[2];
				} else {
					$out[$ts]["note"] = false;
				}

				// lista di droghe da non nascondere
				$drgArr = [md5('massi'), "aulin", "accord", "novalgina", "tachi1000", "actigrip"];
				$curDrg = $out[$ts]['drug'];
				if (in_array($curDrg, $drgArr)) {
					$out[$ts]["toHide"] = false;
				} else {
					$out[$ts]["toHide"] = true;
				}

				$c++;
			}
		}

		krsort($out);

		return $out;
	} else {
		return false;
	}
}

//$boom = ["X", "aulin", "accord", "novalgina", "tachi1000", "actigrip"];
//$pos = array_search( "aueuh",$boom );
//var_dump( $boom, $pos, $boom[$pos]  );

//var_dump(loadDR());

// timeStamp = false, return all Object;
// timeStamp = number, return one object;
function filter($f = false)
{
	$out = [];
	$file = loadDR();

	if ($f) {
		if ($f == "hide-rinvoq") {

			$hideRinvoq = [];
			foreach ($file as $ts => $arr) {
				if ($arr['toHide']) {
				} else {
					$hideRinvoq[$ts] = $arr;
				}
			}

			$out = $hideRinvoq;
		} else {

			$solo = [];
			foreach ($file as $ts => $arr) {
				if ($arr["drug"] == $f) {
					$solo[$ts] = $arr;
				}
			}


			$out = $solo;
		}
	} else {
		$out = $file;
	}

	return $out;
}

function monthsLiist($f = false)
{

	$data = filter($f);
	$months = ["", 'gennaio', 'febbraio', 'marzo', 'aprile', 'Maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];

	foreach ($data as $ts => $arr) {
		$n = date("n", $ts);
		$y = date("Y", $ts);
		$mList["$y$n"]['menu'] = $months[$n] . " $y";
		$mList["$y$n"]['month'] = $n;
		$mList["$y$n"]['year'] = $y;
		$mList["$y$n"]['id'] = "$y/$n";
	}

	foreach ($mList as $n => $arr) {
		$out[] = $arr;
	}

	return $out;
}

function lastDrug($f = false)
{
	$data = filter($f);

	//var_dump($data);

	$arrNoTsK = [];

	foreach ($data as $ts => $ar) {
		$arrNoTsK[] = $ar;
	}


	$lastTs = $arrNoTsK[0]["ts"];
	$lastDrug = $arrNoTsK[0]["drug"];

	$now = time();

	$dateA = new DateTime();
	$dateA->setTimestamp($now);
	$dateB = new DateTime();
	$dateB->setTimestamp($lastTs);
	$DF = date_diff($dateB, $dateA);

	$out["drug"] = $lastDrug;
	$out["diffText"] = "$DF->d giorni, $DF->h ore, $DF->i minuti, $DF->s secondi";
	$out['time'] = date("Y-m-d H:i:s", $lastTs);
	$out['lastTs'] = $lastTs;
	$out['nowTs'] = $now;
	$out["filter"] = $f;

	return $out;
}

function tsDif($f = false)
{

	$filter = filter($f);

	$days = ["domenica", "lunedi", "martedi", "mercoledi", "giovedi", "venerdi", "sabato"];
	$months = ["", 'gennaio', 'febbraio', 'marzo', 'aprile', 'Maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];

	//remove ts key

	$noKeyTs = [];
	foreach ($filter as $ts => $arr) {
		$noKeyTs[] = $arr;
	}


	foreach (range(0, count($noKeyTs)) as $o => $i) {
		$i++;

		$outObj = (object) array();

		if (isset($noKeyTs[$o])) $a = $noKeyTs[$o]["ts"];
		if (isset($noKeyTs[$o])) $arrA = $noKeyTs[$o];

		if (isset($noKeyTs[$i])) $b = $noKeyTs[$i]["ts"];
		if (isset($noKeyTs[$i])) $arrB = $noKeyTs[$i];

		$dateA = new DateTime();
		$dateA->setTimestamp($a);
		$dateB = new DateTime();
		$dateB->setTimestamp($b);
		$DF = date_diff($dateB, $dateA);
		$outObj->dateDiff = $DF;

		$outObj->filer = $f;

		$outObj->TSa = $a;
		$outObj->TSb = $b;

		//$outObj->IDa = $o;
		//$outObj->IDb = $i;

		$outObj->drugA = $arrA['drug'];
		$outObj->drugB = $arrB['drug'];

		$outObj->noteA = hex2bin($arrA['note']);
		$outObj->noteB = hex2bin($arrB['note']);

		$outObj->difText = "$DF->d Giorni, $DF->h Ore e $DF->i Minuti ";
		$outObj->time = date("H:i:s", $a);

		$outObj->Y = date("Y", $a);
		$outObj->M = date("m", $a);
		$outObj->month = $months[date("n", $a)];
		$outObj->D = date("d", $a);
		$outObj->day = $days[date("w", $a)];
		$outObj->h = date("H", $a);
		$outObj->m = date("i", $a);
		$outObj->s = date("s", $a);
		$outObj->date = "$outObj->day $outObj->D $outObj->month $outObj->Y";

		$out[] = $outObj;
	}


	return $out;
}

function drgList($f = false)
{
	$out = [];

	$data = tsDif($f);
	//var_dump($data);

	foreach ($data as $n => $obj) {
		$itm['id'] = $n;
		$itm['time'] = $obj->time;
		$itm['drug'] = $obj->drugA;
		$itm['difText'] = $obj->difText;
		$itm['difDrug'] = $obj->drugB;
		$itm['note'] = $obj->noteA;
		$itm['date'] = $obj->date;
		$itm['ts'] = $obj->TSa;
		$itm['filter'] = $f;
		$itm['success'] = true;

		$out[] = $itm;
	}

	return $out;
}

$drg = ["aulin", "accord", "novalgina", "tachi1000", "actigrip", "rinvoq", "hide-rinvoq", "note", "xeljanz", "custom"];
// per testare $_GET restituisce true/false
function get($v)
{

	if (isset($_GET[$v])) {
		return true;
	} else {
		return false;
	}
}


if (get("log")) {
	$out = drgList();
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($out);

	return true;
}

if (get("filter")) {

	if (is_int(array_search($_GET['filter'], $drg))) {
		$out = drgList($_GET['filter']);
	} else {
		$out["success"] = false;
		$out["drug"] = $_GET["filter"];
		$out["get"] = "filter";
	}

	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($out);

	return true;
}


if (get("now")) {
	$now = $_GET["now"];
	header('Content-Type: application/json; charset=utf-8');

	if ($now == "false") {

		$out = lastDrug();
		//$out['server'] = $_SERVER;
		//$out['ra'] = $_SERVER['REMOTE_ADDR'];
		echo json_encode($out);
	} else {

		if (is_int(array_search($_GET['now'], $drg))) {
			$out = lastDrug($now);
			echo json_encode($out);
		} else {
			$out["success"] = false;
			$out["drug"] = $_GET["now"];
			$out["get"] = "now";
			echo json_encode($out);
		}
	}

	//sleep( rand(1,1000)/100 );

	return true;
}

$php = json_decode(file_get_contents('php://input'));

if (isset($php->call) and $php->call == "login") {

	$test = 0;

	$user = md5("morpe");
	$pass = md5("fufu69hu");

	if ($user == md5($php->user)) $test++;
	if ($pass == md5($php->pass)) $test++;

	if ($test === 2) {
		$out['success'] = true;

		//$time = 60*60*24+time(); // +1giorno
		$time = 60 * 25 + time(); // +25min

		$out["ts"] = "$time";
		$out["msg"] = "welcome $user!";
	} else {
		$out['success'] = false;
		$out["ts"] = "";
	}

	//$out[] = "make login";
	//$out["test"] = $test;
	//$out[] = $php;
	echo json_encode($out);
}

if (isset($php->call) and $php->call == "new") {

	$drg = drgMD5($php->drg);
	$ts = time();
	if (isset($php->note)) {
		$note = bin2hex($php->note);
	} else {
		$note = "";
	}

	$toFile = "$ts/$drg/$note/\n";

	$out['ts'] = time();
	//$out['msg'] = array($toFile,$note,$drg,$php);
	$out['msg'] = $php->drg;

	$f = file_put_contents("dr.log", $toFile, FILE_APPEND);

	//$f = true;
	if ($f) {
		$out['success'] = true;
	} else {
		$out['success'] = false;
	}

	echo json_encode($out);
}

if (isset($php->call) and $php->call == "delete") {

	$log = file("dr.log");
	$LC = count($log) - 1;
	unset($log[$LC]);

	$s = file_put_contents("dr.log", $log);

	$out["success"] = is_int($s);
	$out["ts"] = "";
	$out["msg"] = "";

	$php = json_decode(file_get_contents('php://input'));
	echo json_encode($out);
}


if (isset($php->call) and $php->call == "man") {
	$php = json_decode(file_get_contents('php://input'));
	//$out["get"] = $_GET;
	//$out["php"] = $php;
	//$out["test"] = $php->call == "man";

	//$out["date"] = date("Y-m-d H:i:s");
	//$out["str_date"] = date("Y-m-d H:i:s", strtotime("2022-29-03 11:23") );

	$ts = $php->ts;
	$drug = drgMD5($php->sel);
	$note = bin2hex($php->note);

	if ($php->cdrg != false) {
		$cdrg = bin2hex($php->cdrg);
	} else {
		$cdrg = bin2hex('false');
	}

	$toFile = "$ts/$drug/$note/$cdrg/\n";

	//$out["file"] = $toFile;

	$s = file_put_contents("dr.log", $toFile, FILE_APPEND);

	$out["success"] = is_int($s);
	$out["ts"] = "";
	$out["msg"] = "";

	// ts/md5 drugs/ binnote / custom drug /
	echo json_encode($out);
}

if (get("m-list")) {
	//echo "huuu!!";
	$out = monthsLiist();
	echo json_encode($out);
}

if (get("dump")) {
	var_dump($_GET);

	$php = json_decode(file_get_contents('php://input'));
	var_dump($php);
}

if (get("dummy")) {
	$php = json_decode(file_get_contents('php://input'));

	$out["get"] = $_GET;
	$out["php"] = $php;
	$out["test"] = $php->call == "man";
	echo json_encode($out);
}

/*
$out["success"] = false;
$out["get"] = $_GET;
$out["post"] = $_POST;
$out["php-input"] = false;*
echo json_encode($out);*/
/*
export class PostCall {
	success: boolean = false;
	ts: string = "";
	msg: string = "welcome!";
}
*/