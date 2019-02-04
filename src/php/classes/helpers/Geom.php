<?php

namespace lx;


//todo бывший GeomCalculator + некоторые методы связанные с геометрическими константами
class Geom {
	// public $params = [];

	// public function __construct($str='', $params=[]) {
	// 	$this->params = $params;
	// 	parent::__construct($str);
	// }

	public static function directionByGeom($geom) {
		if ($geom == \lx::LEFT || $geom == \lx::CENTER || $geom == \lx::RIGHT) return \lx::HORIZONTAL;
		if ($geom == \lx::TOP || $geom == \lx::MIDDLE || $geom == \lx::BOTTOM) return \lx::VERTICAL;
		return false;
	}

	public static function geomConst($name) {
		switch ($name) {
			case 'left':   return \lx::LEFT;
			case 'width':  return \lx::WIDTH;
			case 'right':  return \lx::RIGHT;
			case 'top':    return \lx::TOP;
			case 'height': return \lx::HEIGHT;
			case 'bottom': return \lx::BOTTOM;
		}
	}

	public static function geomName($val) {
		switch ($val) {
			case \lx::LEFT :  return 'left';
			case \lx::WIDTH:  return 'width';
			case \lx::RIGHT:  return 'right';
			case \lx::TOP:    return 'top';
			case \lx::HEIGHT: return 'height';
			case \lx::BOTTOM: return 'bottom';
		}
	}

	public static function alignConst($name) {
		switch ($name) {
			case 'left'  : return \lx::LEFT;
			case 'center': return \lx::CENTER;
			case 'right' : return \lx::RIGHT;
			case 'top'   : return \lx::TOP;
			case 'middle': return \lx::MIDDLE;
			case 'bottom': return \lx::BOTTOM;
		}
	}

	public static function alignName($val) {
		switch ($val) {
			case \lx::LEFT  : return 'left';
			case \lx::CENTER: return 'center';
			case \lx::RIGHT : return 'right';
			case \lx::TOP   : return 'top';
			case \lx::MIDDLE: return 'middle';
			case \lx::BOTTOM: return 'bottom';
		}
	}

	public static function soaxisParamNames($param) {
		switch ($param) {
			case 'left'  : return ['right', 'width'];
			case 'right' : return ['left', 'width'];
			case 'width' : return ['right', 'left'];
			case 'top'   : return ['bottom', 'height'];
			case 'bottom': return ['top', 'height'];
			case 'height': return ['top', 'bottom'];
		}
	}

	/**
	 * $str - шаблон расчета, н-р (a + b) * 2 * c
	 * $params - массив значений для подстановки в пример, где ключи - имена переменных в шаблоне
	 * */
	public static function calculate($str, $params) {
		$geomParams = [];
		$map = [];

		foreach ($params as $key => $param) {
			if (is_string($param) || is_numeric($param)) {
				$val = self::splitGeomValue($param);
				$geomParams[] = $val;
				$map['/\b' . $key . '\b/'] = $val[0];
				continue;
			}

			//todo была возможность в параметрах передавать элементы, а в формуле писать a.right + b.width
			// preg_match_all('/' . $key . '\.(\w+?\b)/', $str, $geomNames);
			// foreach ($geomNames[1] as $geomName) {
			// 	$geomParams[] = $param->getGeomParam($geomName);
			// 	$map['/\b' . $key . '\.' . $geomName . '\b/'] = $param->$geomName();
			// }
		}

		if (!self::sameGeomParams($geomParams)) return false;

		foreach ($map as $pattern => $value)
			$str = preg_replace($pattern, $value, $str);

		return Math::calculate($str) . $geomParams[0][1];
	}

	public static function splitGeomValue($val) {
		if (is_numeric($val)) return [$val, ''];
		$n = floatval($val);
		$format = explode($n, $val)[1];
		return [$n, $format];
	}

	public static function sameGeomParams($args) {
		$map = [
			'%' => 0,
			'px' => 0,
			'' => 0
		];
		foreach ($args as $value) {
			if ($value[1] === false) return false;
			$map[ $value[1] ]++;
			if ($map['%'] && $map['px']) return false;
		}
		return true;
	}
}
