<?php

namespace lx;

class FileAnalyzer {
	private $path;
	private $file;
	private $fullCode;
	private $cleanCode;
	private $strings;

	public function __construct($path) {
		$this->path = $path;
		$this->cleanCode = null;
		if (file_exists($path)) {
			$this->file = new File($path);
			$this->fullCode = $this->file->get();
		}

		$this->analyzeCode();
	}






	private function analyzeCode() {
		$this->makeCleanCode();


	}

	private function makeCleanCode() {
		if ($this->cleanCode !== null) return;

		$cleaner = new CodeCleaner($this->fullCode);
		$cleaner->clean();

		var_dump($cleaner);

		die();
	}
}
















/*
Идея себя не оправдала - посимвольный разбор кода очень медленный
Нужно извращаться с регулярками, либо как-то использовать что-то вроде C-скриптов
*/
class CodeCleaner {
	private $code = '';
	private $cleanCode = '';
	private $strings = [];

	private $inCommentLine = false;
	private $inComment = false;
	private $inString = false;
	private $inStringPoly = false;
	private $preSymbol = '';

	public function __construct($code) {
		$this->code = $code;
	}

	public function clean() {
		$len = mb_strlen($this->code);
		for ($i=0; $i<$len; $i++) {
			$symbol = mb_substr($this->code, $i, 1);

			if ($this->inCode()) {
				if ($this->checkChangeContext($symbol)) continue;
				$this->cleanCode .= $symbol;
				$this->preSymbol = $symbol;
			} elseif ($this->inComment) {
				if ($this->preSymbol == '*' && $symbol == '/') {
					$this->inComment = false;
					$this->preSymbol = '';
					continue;
				}
				$this->preSymbol = $symbol;
			} elseif ($this->inCommentLine) {
				if (preg_match('/(\r|\n|\r\n)/', $symbol)) {
					$this->inCommentLine = false;
					$this->preSymbol = '';
					continue;
				}
				$this->preSymbol = $symbol;
			} elseif ($this->inString) {
				$strData = end($this->strings);
				if ($symbol == $strData['quote']) {
					$this->inString = false;
					$this->preSymbol = '';
				} else {
					$strData['text'] .= $symbol;
					$this->preSymbol = $symbol;
				}
			}
		}
	}

	private function checkChangeContext($symbol) {
		if ($symbol == '"' || $symbol == "'") {
			$this->inString = true;
			$this->preSymbol = $symbol;
			$this->cleanCode .= '#lx:str('. count($this->strings) .');';
			$this->strings[] = [
				'quote' => $symbol,
				'text' => '',
			];
			return true;
		}

		if ($this->preSymbol == '/') {
			if ($symbol == '*') {
				$this->inComment = true;
				$this->preSymbol = $symbol;
				return true;
			} elseif ($symbol == '/') {
				$this->inCommentLine = true;
				$this->preSymbol = $symbol;
				return true;
			} else {
				$this->cleanCode .= '/';
			}
		} elseif ($symbol == '/') {
			$this->preSymbol = $symbol;
			return true;
		}

		return false;
	}

	private function inCode() {
		return !$this->inComment
			&& !$this->inCommentLine
			&& !$this->inString
			&& !$this->inStringPoly;
	}
}
