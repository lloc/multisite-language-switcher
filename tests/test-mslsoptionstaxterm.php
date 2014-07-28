<?php
/**
 * Tests for MslsOptionsTaxTerm
 *
 * @author Dennis Ploetner <re@lloc.de>
 * @package Msls
 */

/**
 * WP_Test_MslsOptionsTaxTerm
 */
class WP_Test_MslsOptionsTaxTerm extends Msls_UnitTestCase {

	/**
	 * Verify the check_url-method
	 */
	function test_check_url_method() {
		$obj = new MslsOptionsTaxTerm( 0 );
		$this->assertInternalType( 'string', $obj->check_url( '' ) );
		return $obj;
	}

	/**
	 * Verify the get_tax_query-method
	 * @depends test_check_url_method
	 */
	function test_get_base_method( $obj ) {
		$this->assertInternalType( 'string', $obj->get_base() );
		$this->assertEquals( 'tag', $obj->get_base() );
	}

}