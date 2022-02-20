<?php

namespace lx;

class Math
{
	private static int $counter = 0;

	public static function randHash(): string
	{
		return md5('' . (self::$counter++) . '_' . time() . '_' . rand(0, PHP_INT_MAX));
	}

	public static function rand(int $min, int $max, bool $crypt = false): int
	{
		if ($crypt) {
			return \random_int($min, $max);
		}

		return \rand($min, $max);
	}

	public static function gamble(float $probability): bool
	{
	    if ($probability <= 0.0) {
	        return false;
        }
	    if ($probability >= 1.0) {
	        return true;
        }
	    
		$param = $probability * 1000;
		$rand = self::rand(1, 1000);
		return $rand <= $param;
	}

	public static function shuffleArray(array $array): array
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
	 */
	public static function decChangeNotation(int $n, int $basis): string
	{
	    $basis = self::intApplyLimits($basis, 2, 62);
	    
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
	
	public static function intApplyLimits(int $value, int $min, int $max): int
    {
        if ($value > $max) {
            return $max;
        }
        
        if ($value < $min) {
            return $min;
        }
        
        return $value;
    }

	public static function roundToZero(float $val): int
	{
		if ($val > 0) return floor($val);
		if ($val < 0) return ceil($val);
		return 0;
	}

	/**
	 * Calculate result from string, example: '(11 + 44) * 3'
	 */
	public static function calculate(string $str): ?float
	{
		if ($str == '') {
		    return 0.0;
        }
		if (is_numeric($str)) {
		    return (float)$str;
        }
		$str = preg_replace('/^=/', '', $str);
		$str = str_replace(' ', '', $str);
		if (preg_match('/[^\d.()+\-*\/]/', $str)) {
		    return null;
        }
		if (is_numeric($str)) {
		    return (float)$str;
        }

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
