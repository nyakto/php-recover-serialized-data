<?php
class SerializationUtil {
	public static function serialize($data) {
		return serialize($data);
	}

	public static function unserialize($str) {
		$data = @unserialize($str);
		if ($data === false and $str !== 'b:0;') {
			$data = recovery($str);
		}
		return $data;
	}

	public static function recovery($str) {
		$pos = 0;
		return self::readValue($str, $pos);
	}

	public static function readValue($text, &$pos) {
		$type = substr($text, $pos, 1);
		switch ($type) {
			case 'N':
				return self::readNull($text, $pos);;
			case 'i':
				return self::readInteger($text, $pos);
			case 'd':
				return self::readDouble($text, $pos);
			case 's':
				return self::readString($text, $pos);
			case 'b':
				return self::readBoolean($text, $pos);
			case 'a':
				return self::readArray($text, $pos);
		}
		throw new Exception('unsupported value type: '.$type);
	}

	public static function readNull($text, &$pos) {
		$pos += 2;
		return null;
	}

	public static function readInteger($text, &$pos) {
		$len = strlen($text);
		$buff = '';
		for ($i = $pos + 2; $i < $len; ++$i) {
			$c = substr($text, $i, 1);
			if ($c === ';') {
				$pos = $i + 1;
				return intval($buff);
			}
			$buff .= $c;
		}
	}

	public static function readDouble($text, &$pos) {
		$len = strlen($text);
		$buff = '';
		for ($i = $pos + 2; $i < $len; ++$i) {
			$c = substr($text, $i, 1);
			if ($c === ';') {
				$pos = $i + 1;
				return floatval($buff);
			}
			$buff .= $c;
		}
	}

	public static function readString($text, &$pos) {
		$buff = '';
		$len = strlen($text);
		for ($i = $pos + 2; $i < $len; ++$i) {
			$c = substr($text, $i, 1);
			if ($c === ':') {
				break;
			}
			$buff .= $c;
		}
		$length = intval($buff);
		$shift = $pos + 4 + strlen($buff);
		if (self::isValidStringEnding($text, $shift + $length)) {
			$pos = $shift + $length + 2;
			return substr($text, $shift, $length);
		}
		$l = $shift + $length - 1;
		$r = $l + 2;
		$ll = $shift;
		$rl = $len - 2;
		while ($l >= $ll or $r <= $rl) {
			if ($l >= $ll) {
				if (self::isValidStringEnding($text, $l)) {
					$pos = $l + 2;
					return substr($text, $shift, $l - $shift);
				}
				$l--;
			}
			if ($r <= $rl) {
				if (self::isValidStringEnding($text, $r)) {
					$pos = $r + 2;
					return substr($text, $shift, $r - $shift);
				}
				$r++;
			}
		}
		throw new Exception('failed to recover string');
	}

	public static function readBoolean($text, &$pos) {
		$v = substr($text, $pos + 2, 1);
		$pos += 4;
		return $v === '1';
	}

	public static function readArray($text, &$pos) {
		$buff = '';
		$len = strlen($text);
		for ($i = $pos + 2; $i < $len; ++$i) {
			$c = substr($text, $i, 1);
			if ($c === ':') {
				break;
			}
			$buff .= $c;
		}
		$size = intval($buff);
		$shift = $pos + 4 + strlen($buff);
		$result = array();
		for ($i = $shift; $i < $len;) {
			$c = substr($text, $i, 1);
			if ($c === '}') {
				$pos = $i + 1;
				break;
			}
			$key = self::readValue($text, $i);
			$value = self::readValue($text, $i);
			$result[$key] = $value;
		}
		return $result;
	}

	public static function isValidStringEnding($text, $pos) {
		if (substr($text, $pos, 2) !== '";') {
			return false;
		}
		return preg_match('~^($|}|[idbsa]:|N;)~su', substr($text, $pos + 2));
	}
}
