<?php
namespace dev;

/*

Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Cache test page
 *
 * @author Vincent Guth
 */
abstract class CacheTest extends \Test {

	protected $cache;

	/**
	 * Basic get(), exists() and set() test
	 */
	public function testBasicGetSetExists() {

		$this->assertFalse($this->cache->exists('toto'));
		$this->assertTrue($this->cache->set('toto', 'plop'));
		$this->assertTrue($this->cache->exists('toto'));
		$this->assertTrue($this->cache->get('toto') === 'plop');

		$this->assertTrue($this->cache->set('int', 6));
		$this->assertTrue($this->cache->get('int') === 6);

		$this->assertTrue($this->cache->delete('int'));
		$this->assertFalse($this->cache->exists('int'));
		$this->assertFalse($this->cache->delete('int'));

	}

	/**
	 * Timeout test
	 */
	public function testTimeout() {

		$this->assertTrue($this->cache->set('toto', 'plap', 1));
		usleep(1100000);
		$this->assertFalse($this->cache->get('toto'));

		$this->assertTrue($this->cache->set('titi', 'plap', time()));
		usleep(100000);
		$this->assertFalse($this->cache->get('titi'));

		$this->assertTrue($this->cache->set('tutu', 'plap'));
		$this->assertTrue($this->cache->exists('tutu'));
		$this->assertTrue($this->cache->setTimeout('tutu', time() + 1));
		usleep(1100000);
		$this->assertFalse($this->cache->get('tutu'));

	}

	/**
	 * Array get() test
	 */
	public function testStrangeGetSetExists() {

		$this->assertTrue($this->cache->set('totarray', ['plip', 'plop']));
		$this->assertTrue($this->cache->exists('totarray'));

		$this->assertTrue($this->cache->get('totarray') === ['plip', 'plop']);

		$this->assertTrue($this->cache->set('totstd', new \stdClass()));
		$this->assertTrue($this->cache->get('totstd') instanceof \stdClass);

	}

	/**
	 * query() test
	 */
	public function testQuery() {

		$this->assertFalse($this->cache->exists('tata'));
		$this->assertTrue($this->cache->query('tata', function() {
			return 'plup';
		}) === 'plup');
		$this->assertTrue($this->cache->get('tata') === 'plup');

	}

	/**
	 * add() test
	 */
	public function testAdd() {

		$this->assertTrue($this->cache->add('piou', 'miou'));
		$this->assertTrue($this->cache->get('piou') === 'miou');

		$this->assertFalse($this->cache->add('piou', 'kiou'));
		$this->assertTrue($this->cache->get('piou') === 'miou');

	}

	/**
	 * get() several test
	 */
	public function testGetSeveral() {

		$this->cache->set('flip', 'la');
		$this->cache->set('flop', 'girafe');

		$this->assertTrue($this->cache->get(['flip', 'flop']) === ['flip' => 'la', 'flop' => 'girafe']);
		$this->assertTrue($this->cache->get(['flip', 'flap']) === ['flip' => 'la', 'flap' => FALSE]);
		$this->assertTrue($this->cache->get(['flup', 'flap']) === ['flup' => FALSE, 'flap' => FALSE]);

	}

	/**
	 * Increment test
	 */
	public function testIncrement() {

		$this->cache->increment('increment', 1);
		$this->assertTrue($this->cache->counter('increment') === 1);

		$this->cache->increment('increment', 3);
		$this->assertTrue($this->cache->counter('increment') === 4);

	}

	/**
	 * Decrement test
	 */
	public function testDecrement() {

		$this->cache->counter('decrement', 100);

		$this->cache->decrement('decrement', 1);
		$this->assertTrue($this->cache->counter('decrement') === 99);

		$this->cache->decrement('decrement', 3);
		$this->assertTrue($this->cache->counter('decrement') === 96);

	}

	/**
	 * Append test
	 */
	public function testAppend() {

		$this->cache->append('mou', 'bonjour');
		$this->assertFalse($this->cache->exists('mou'));

		$this->cache->set('mou', '');
		$this->cache->append('mou', 'bonjour');
		$this->assertTrue($this->cache->get('mou') === 'bonjour');

		$this->cache->append('mou', ' ??a va');
		$this->assertTrue($this->cache->get('mou') === 'bonjour ??a va');

	}

	/**
	 * Prepend test
	 */
	public function testPrepend() {

		$this->cache->prepend('dur', '??a va');
		$this->assertFalse($this->cache->exists('dur'));

		$this->cache->set('dur', '');
		$this->cache->prepend('dur', '??a va');
		$this->assertTrue($this->cache->get('dur') === '??a va');

		$this->cache->prepend('dur', 'bonjour ');
		$this->assertTrue($this->cache->get('dur') === 'bonjour ??a va');

	}

	/**
	 * Flush test
	 */
	public function testFlush() {

		$this->cache->set('toto', 'plop');
		$this->assertTrue($this->cache->get('toto') === 'plop');

		$this->cache->flush();

		$this->assertTrue($this->cache->get('toto') === FALSE);

	}

}

?>
