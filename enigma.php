#!/usr/bin/php
<?php

# Nicholas Ferreira
# 30/12/2021

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


# ================ Variable settings
//Reflectors set (left to right)
//$r_set = array_reverse([$rotors[0][1], $rotors[0][2], $rotors[0][3]]);

//Starting positions of each rotor (left to right)
$starting_positions = "AAA";

//Max 13 pairs
$plugboard = ["AB", "GE", "PL", "JL", "HW", "MR", "", "", "", "", "", "", ""];

//$reflector = [$rfB];

# ================ Functions

//Remove non-alphabetic characters
function parse($str){
	$str = strtoupper($str);
	$str = preg_replace("/[^A-Z ]/", "", $str);
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
		array_push($ret, substr($rot,$n,strlen($rot)).substr($rot,0,$n));	//abcd => bcda
	return $ret;
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

function full_rotate($str, $rotor_list, $reflector){
	global $notch,$rotors,$e_wheel;
	//Left, mid and right rotors

//	print_r($rotors);

	$leftR = $rotors[0][$rotor_list[0]];
	$midR = $rotors[0][$rotor_list[1]];
	$rightR = $rotors[0][$rotor_list[2]];

	$leftRR = $rotors[1][$rotor_list[0]];
	$midRR = $rotors[1][$rotor_list[1]];
	$rightRR = $rotors[1][$rotor_list[2]];

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
	for($i=0;$i<strlen($str);$i++){
		if($rightR[0] == $rightNotch){
			if($midR[0] == $midNotch){
				$leftCount++;
				$leftR = rotate(3, $leftCount); //change number
				echo "left rotor rotated\n";
			}
			$midCount++;
//			$midCount = ($midCount == 27) ? 1 : $midCount;
			$rotatedM = rotate(2,$midCount); //change number
			$midR = $rotatedM[0];
			$midRR = $rotatedM[1];
	//		echo "mid rotor rotated\n";
		}

		//Rotate the right rotor
//		echo "R: ".$rightR."\n";
//		echo "M: ".$midR."\n";
//		echo "L: ".$leftR."\n";

//		echo $rightR."\n";
		$rotatedR = rotate(1,$i+1); //change number
		$rightR = $rotatedR[0];
		$rightRR = $rotatedR[1];

		$r = [$leftR, $midR, $rightR];
		$rr = [$leftRR, $midRR, $rightRR];

//		echo"r: ".print_r($r);
//		echo":rr: ".print_r($rr);

//		print_r(reverse($rotors));
		$char = $str[$i];

		//r3 -> r2 -> r1 -> reflector -> rr1 -> rr2 -> rr3 (rr = reversed rotor)



		$full_rotate_arr = array_merge(array_reverse($r), [$reflector], $rr);

//		print_r($full_rotate_arr);
//		echo sprintf("%02d",$i).":".$midCount." ".$char."->";
		$char = pass_to_rotor($char, $rightR);

		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - ($i+1))%26];
//		echo $char."->";
		$char = pass_to_rotor($char, $midR);
		$char = $e_wheel[array_search($char,str_split($e_wheel)) - ($midCount)];
//		echo $i." ".$midCount."\n";
//		echo $char."->";
		$char = pass_to_rotor($char, $leftR);
//		echo $char."->";

		$char = pass_to_rotor($char, $reflector);
//		echo $char."->";

		$char = pass_to_rotor($char, $leftRR);
//		echo $char."->";
		$char = pass_to_rotor($char, $midRR);
		$char = $e_wheel[array_search($char,str_split($e_wheel)) - ($midCount)];

//		echo $char."->";
		$char = pass_to_rotor($char, $rightRR);
		$char = $e_wheel[(array_search($char,str_split($e_wheel)) - ($i+1))%26];
		echo $char;
/*
		foreach($full_rotate_arr as $r){
			echo $char."->";
			$char = pass_to_rotor($char, $r);
		}*/

		//print_r("\n".$rightR."\n");
	//	echo "\n";

//		echo print_r($rightR)."\n";
	}

//		echo all_rotors($str[$i], $r_set)."\n";
}

# ================

//$string = parse("AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA");
$string = parse($argv[1]);

echo full_rotate($string, [3,2,1], $rfB);
