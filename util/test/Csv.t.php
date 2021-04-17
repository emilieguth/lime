<?php
namespace util;

/*

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * CSV package test page
 *
 * @author Laurent Bardin
 */
class CsvTest extends \Test {

	public $csv;

	public function init() {
		$this->csv = new \util\CsvLib();
	}

	public function testCsv() {
		$this->assertEquals("foo\n", $this->csv->toCsv([['foo']]));
		$this->assertEquals("foo;bar\n", $this->csv->toCsv([['foo','bar']]));
		$this->assertEquals("foo\nbar\n", $this->csv->toCsv([['foo'], ['bar']]));

		$this->assertEquals("\"foo;bar\"\n", $this->csv->toCsv([['foo;bar']]));
		$this->assertEquals("\"foo ;bar\"\n", $this->csv->toCsv([['foo ;bar']]));
		$this->assertEquals("\"foo;b ar\"\n", $this->csv->toCsv([['foo;b ar']]));
		$this->assertEquals("foo;\"bar \"\n", $this->csv->toCsv([['foo','bar ']]));
		$this->assertEquals("\"fo\"\"o\"\nbar\n", $this->csv->toCsv([['fo"o'], ['bar']]));

		$this->assertEquals("\"foo\nbar\"\n", $this->csv->toCsv([["foo\nbar"]]));
		$this->assertEquals("\"foo\r\nbar\"\n", $this->csv->toCsv([["foo\r\nbar"]]));
		$this->assertEquals("\"foo;b\"\"ar\"\n", $this->csv->toCsv([['foo;b"ar']]));
	}

}

?>
