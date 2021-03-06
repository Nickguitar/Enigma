#!/usr/bin/php
<?php

# ============== Fixed settings
//Entry wheel
$e_wheel = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

/*
Rotors wiring configuration
(From the original 1930 Enigma I machine)
https://www.cryptomuseum.com/crypto/enigma/wiring.htm
*/
$r1 = "EKMFLGDQVZNTOWYHXUSPAIBRCJ";
$r2 = "AJDKSIRUXBLHWTMCQGZNPYFVOE";
$r3 = "BDFHJLCPRTXVZNYEIWGAKMUSQO";
$r4 = "ESOVPZJAYQUIRHXLNFTGKDCMWB";
$r5 = "VZBRGITYUPSDNHLXAWMJQOFECK";

/*
Reverse rotors (after the current hits the reflector)

The idea is this: the rotor 3 does this transformation:

ABCDEFGHIJKLMNOPQRSTUVWXYZ
↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
BDFHJLCPRTXVZNYEIWGAKMUSQO

Sorting alphabetically the bottom row (maintaining the mapping):

TAGBPCSDQEUFVNZHYIXJWLRKOM
↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
ABCDEFGHIJKLMNOPQRSTUVWXYZ

By sorting the bottom row, the first row gets scrambled
This new order (TAGB...) is the rotor 3 reversed.

Code to generate the rotor rx reversed:

for($i=0;$i<strlen($rx);$i++)
	$result .= $e_wheel[strpos($rx,$e_wheel[$i])];
*/

$rr1 = "UWYGADFPVZBECKMTHXSLRINQOJ";
$rr2 = "AJPCZWRLFBDKOTYUQGENHXMIVS";
$rr3 = "TAGBPCSDQEUFVNZHYIXJWLRKOM";
$rr4 = "HZWVARTNLGUPXQCEJMBSKDYOIF";
$rr5 = "QCYLXWENFTZOSMVJUDKGIARPHB";


/*
Reflector wiring configuration
A was used before the war,
B and C were used during the war (predominantly B)
*/
$rfA = "EJMZALYXVBWFCRQUONTSPIKHGD";
$rfB = "YRUHQSLDPXNGOKMIEBFZCWVJAT";
$rfC = "FVPJIAOYEDRZXWGCTKUQSBNMHL";

//Notch positions of each rotor
$notch = [
	$r1 => "Q",
	$r2 => "E",
	$r3 => "V",
	$r4 => "J",
	$r5 => "Z"
];

//Rotors and they reversed
$rotors = [
	["", $r1, $r2, $r3, $r4, $r5],
	["", $rr1, $rr2, $rr3, $rr4, $rr5]
];

# ================ Functions

//Remove non-alphabetic characters
function parse($str){
	$str = strtoupper($str);
	$str = preg_replace("/[^A-Z\t+]/", "", $str);
	return $str;
}

//Shift the rotor to the left by $n
//rotate(abcdefghijk, 3) = defghijkabc
function rotate($rotor_number,$n){
	global $rotors;
	$n = $n % 26;
	$r = [$rotors[0][$rotor_number], $rotors[1][$rotor_number]];
	$ret = [];
	foreach($r as $rot)
		array_push($ret, rotate_aux($rot,$n));
	return $ret;
}
//abcdef => bcdefa
function rotate_aux($str, $n){
	return substr($str,$n,strlen($str)).substr($str,0,$n);
}

/*
Pass the char through the specified rotor

Get the current letter index on the
entry wheel and swap it to the rotor
(Spaces are preserved)
*/
function pass_to_rotor($char, $rotor){
	global $e_wheel;
	return $char == " " ? " " : $rotor[strpos($e_wheel, $char)];
}

/*
Simple substitution based on plugboard connections
If plugboard=['AB', 'RF'],
A will be replaced with B in the string and vice-versa,
R will be replaced with F and vice-versa, etc.
*/
function plugboard($string, $plugboard){
	$plugboard = explode(" ", $plugboard);
	parse_plugboard($plugboard);
	foreach($plugboard as $p){
		if($p == "") continue;

		$string = str_replace($p[0], "!", $string);
		$string = str_replace($p[1], $p[0], $string);
		$string = str_replace("!", $p[1], $string);

	}
	return $string;
}


//Makes sure the plugboard configs are ok
function parse_plugboard($plugboard){
	if(count($plugboard) > 13)
		die("[-] Maximum plugboard connections is 13.");
	if(count($plugboard) == 1)
		return;
	foreach($plugboard as $p){
		if(strlen($p) !== 2 && strlen($p) !== 0)
			die("[-] Invalid plugboard connections.\nEach connection must be a pair of letters separated by a space");
		$p0 = $p[0];
		$p1 = $p[1];

		$counter = 0;
		foreach($plugboard as $q)
			if(preg_match("/$p0/", $q) || preg_match("/$p1/", $q))
				$counter++;
		if($counter > 1)
			die("[-] Inconsistent plugboard connections.\nA letter cannot connect to more than one letter.");
	}
}

function full_rotate($str, $rotor_list, $reflector){
	global $notch,$rotors,$e_wheel,$verbose;

	//Left, mid and right rotors
	$leftR = $rotors[0][$rotor_list[0]];
	$midR = $rotors[0][$rotor_list[1]];
	$rightR = $rotors[0][$rotor_list[2]];

	//Left, mid and right rotors (reversed)
	$leftRR = $rotors[1][$rotor_list[0]];
	$midRR = $rotors[1][$rotor_list[1]];
	$rightRR = $rotors[1][$rotor_list[2]];

	//Notches
	$rightNotch = $rightR[array_search($notch[$rightR], str_split($e_wheel))];
	$midNotch = $midR[array_search($notch[$midR], str_split($e_wheel))];


	/*
	For each char of the string,
	Check if the rightmost notch is set.
		If so, check the mid notch is set.
			If so, rotate the left rotor
	 	Rotate the mid rotor
	And Rotate the right rotor
	*/
	$midCount = 0;
	$leftCount = 0;

	$ret ="";
	$leftRotated = false;

	for($i=0;$i<strlen($str);$i++){

		//Current char
		$char = $str[$i];

		$rightRNumber = array_search(rotate_aux($rightR,(-$i % 26)), $rotors[0]);
		$midRNumber = array_search(rotate_aux($midR,(-$midCount % 26)), $rotors[0]);
		$leftRNumber = array_search(rotate_aux($leftR,(-$leftCount % 26)), $rotors[0]);

		/*
		The middle rotor will rotate when:
			1. The right rotor's notch is set
			2. The left rotor's notch is set
		This is because both notches allow the
		pawl to push the middle rotor up.
		It is called double-stepping.
		More information here: http://users.telenet.be/d.rijmenants/en/enigmatech.htm
		This video illustrates this event: https://www.youtube.com/watch?v=hcVhQeZ5gI4
		*/

		if($leftRotated){
			$rotatedM = rotate($midRNumber, $midCount);
			$midR = $rotatedM[0];
			$midRR = $rotatedM[1];
			$leftRotated = false;
//			echo "mid rotated 2\n";
		}

		if($rightR[0] == $rightNotch || $midR[0] == $midNotch){
			if($midR[0] == $midNotch){
				$leftCount++;
				$rotatedL = rotate($leftRNumber, $leftCount);
				$leftR = $rotatedL[0];
				$leftRR = $rotatedL[1];
//				echo "left rotor rotated\n";
				$leftRotated = true;
			}
			$midCount++;
			$rotatedM = rotate($midRNumber,$midCount);
			$midR = $rotatedM[0];
			$midRR = $rotatedM[1];
//			echo "mid rotor rotated\n";
		}

		//Rotate the right rotor
		$rotatedR = rotate($rightRNumber,$i+1);
		$rightR = $rotatedR[0];
		$rightRR = $rotatedR[1];

		$r = [$leftR, $midR, $rightR];
		$rr = [$leftRR, $midRR, $rightRR];

		echo $verbose ? $char."->" : "";

		/*
		Pass the current char to all rotors,
		the plugboard, and the rotors reversed

		E.g.:
		R3 -> R2 -> R1 -> Reflector -> RR1 -> RR2 -> RR3 (RR = reversed rotor)

		*/

		$char = pass_to_rotor($char, $rightR);
		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - ($i+1))%26];
		echo $verbose ? $char."->" : "";

		$char = pass_to_rotor($char, $midR);
		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - $midCount) % 26];
		echo $verbose ? $char."->" : "";

		$char = pass_to_rotor($char, $leftR);
		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - $leftCount) % 26];
		echo $verbose ? $char."->" : "";

		$char = pass_to_rotor($char, $reflector);
		echo $verbose ? $char."->" : "";

		$char = pass_to_rotor($char, $leftRR);
		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - $leftCount) % 26];
		echo $verbose ? $char."->" : "";

		$char = pass_to_rotor($char, $midRR);
		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - $midCount) % 26];

		echo $verbose ? $char."->" : "";
		$char = pass_to_rotor($char, $rightRR);
		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - ($i+1))%26];
		$ret .= $char;
		echo $verbose ? $char."\n" : "";

	}
	return $ret;
}

/*
Pass the string to the plugboard, all rotors, reflector,
all rotors (reversed) and plugboard again.
*/
function enigma($string, $rotors, $reflector, $plugboard){
	$rotors = str_split($rotors);
	$string = plugboard($string, $plugboard);
	$string = full_rotate($string, $rotors, $reflector);
	$string = plugboard($string, $plugboard);
	return $string;
}

# ================



//TODO: Starting positions of each rotor (left to right)
$starting_positions = "AAB";

//Max 13 pairs
$plugboard = "";

$verbose = 0;

if(count($argv) == 1)
	die("Usage: {$argv[0]} <string>");

$string = parse($argv[1]);

echo enigma($string, 123, $rfB, $plugboard);
