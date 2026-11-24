<?php
/**
 * The base test case: WP_UnitTestCase with the fixture methods of the test library in use.
 *
 * The test libraries of WordPress 5.7 and 5.8 declare WP_UnitTestCase_Base as a subclass of PHPUnit's
 * TestCase, with setUp() and tearDown(); from 5.9 it extends the PHPUnit Polyfills adapter, with set_up()
 * and tear_down(). A test class has to override the pair of the library it runs on, so the two
 * declarations below forward to prepare() and clean(), which the test classes override instead. Both
 * libraries call the static wpSetUpBeforeClass( $factory ) and wpTearDownAfterClass() hooks, which the
 * helpers trait provides.
 *
 * @package Exports_And_Reports
 */

if ( method_exists( 'WP_UnitTestCase_Base', 'set_up' ) ) {
	/**
	 * On the test library of WordPress 5.9 and later.
	 */
	abstract class Exports_Reports_Test_Case extends WP_UnitTestCase {

		use Exports_Reports_Test_Helpers;

		/**
		 * Before each test.
		 */
		public function set_up() {
			parent::set_up();
			$this->prepare();
		}

		/**
		 * After each test.
		 */
		public function tear_down() {
			$this->clean();
			parent::tear_down();
		}
	}
} else {
	/**
	 * On the test library of WordPress 5.7 and 5.8.
	 */
	abstract class Exports_Reports_Test_Case extends WP_UnitTestCase {

		use Exports_Reports_Test_Helpers;

		/**
		 * Before each test.
		 */
		public function setUp() {
			parent::setUp();
			$this->prepare();
		}

		/**
		 * After each test.
		 */
		public function tearDown() {
			$this->clean();
			parent::tearDown();
		}
	}
}
