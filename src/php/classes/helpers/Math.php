<?php

namespace lx;

/**
 * Class Math
 * @package lx
 */
class Math
{
	/** @var int */
	private static $counter = 0;

	/**
	 * @return string
	 */
	public static function randHash()
	{
		return md5('' . (self::$counter++) . '_' . time() . '_' . rand(0, PHP_INT_MAX));
	}

	/**
	 * @param int $min
	 * @param int $max
	 * @param bool $crypt
	 * @return int
	 * @throws \Exception
	 */
	public static function rand($min, $max, $crypt = false)
	{
		if ($crypt) {
			return \random_int($min, $max);
		}

		return \rand($min, $max);
	}

	/**
	 * @param float $probability - value from interval [0; 1]
	 * @return bool
	 */
	public static function gamble($probability)
	{
		$param = $probability * 1000;
		$rand = self::rand(1, 1000);
		return $rand <= $param;
	}

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
	public static function shuffleArray($array)
    {
        for ($i=0, $l=count($array); $i<$l; $i++) {
            $rand = Math::rand(0, $l - 1);
            $temp = $array[$i];
            $array[$i] = $array[$rand];
            $array[$rand] = $temp;
        }

        return $array;
    }

	/**
	 * Change the number system from decimal to custom system
	 * You can use basis up to 62
	 *
	 * @param int $n
	 * @param int $basis
	 * @return string
	 */
	public static function decChangeNotation($n, $basis)
	{
		$str = '';
		$q = floor($n / $basis);
		while ($q) {
			$a = $n % $basis;
			if ($a>35) $a = chr($a+29);
			else if ($a>9) $a = chr($a+87);
			$str = "$a$str";
			$n = $q;
			$q = floor($n / $basis);
		}
		$a = $n % $basis;
		if ($a>35) $a = chr($a+29);
		else if ($a>9) $a = chr($a+87);
		$str = "$a$str";
		return $str;
	}

	/**
	 * @param float $val
	 * @return int
	 */
	public static function roundToZero($val)
	{
		if ($val > 0) return floor($val);
		if ($val < 0) return ceil($val);
		return 0;
	}

	/**
	 * Calculate result from string, example: '(11 + 44) * 3'
	 *
	 * @param string $str
	 * @return float|false
	 */
	public static function calculate($str)
	{
		if ($str == '') return 0;
		if (is_numeric($str)) return $str;
		$str = preg_replace('/^=/', '', $str);
		$str = str_replace(' ', '', $str);
		if (preg_match('/[^\d.()+\-*\/]/', $str)) return false;
		if (is_numeric($str)) return $str;

		$calc = function($op0, $op1, $opr) {
			switch ($opr) {
				case '+': return $op0 + $op1;
				case '-': return $op0 - $op1;
				case '/': return $op0 / $op1;
				case '*': return $op0 * $op1;
			}		
		};

		$applyOperation = function(&$i, &$l, $op, &$nums, &$opers) use ($calc) {
			$num = $calc(floatval($nums[$i]), floatval($nums[$i+1]), $op);
			$nums[$i] = $num;
			array_splice($nums, $i+1, 1);
			array_splice($opers, $i, 1);
			$i--;
			$l--;
		};

		$simpleCalc = function($str) use ($applyOperation) {
			$nums = preg_split('/[\*\/+-]/', $str);
			preg_match_all('/[\*\/+-]/', $str, $opers);
			$opers = $opers[0];
			$oprPrioritet = ['*', '/'];
			$l = count($opers);
			for ($i=0; $i<$l; $i++) {
				$oper = $opers[$i];
				if (array_search($oper, $oprPrioritet) === false) continue;
				$applyOperation($i, $l, $oper, $nums, $opers);
			}
			$l = count($opers);
			for ($i=0; $i<$l; $i++) {
				$oper = $opers[$i];
				$applyOperation($i, $l, $oper, $nums, $opers);
			}
			return $nums[0];
		};

		$simp = explode('(', $str);
		for ($i=count($simp)-1; $i>0; $i--) {
			$s = $simp[$i];
			preg_match('/\)/', $s, $close, PREG_OFFSET_CAPTURE);
			$inner = substr($s, 0, $close[0][1]);
			$simp[$i - 1] .= $simpleCalc($inner) . substr($s, $close[0][1] + 1);
		}
		$result = $simpleCalc($simp[0]);
		return $result;
	}
}
