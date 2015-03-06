<?php
/**
 * This file contains the associations between modules and identifiers.
 * It is needed to dertermine if a user is trying to rate an own post.
 * This file will be included normally (not via codeigniter).
 * There a 3 parameters accessible for this file:
 * 		$userid     - The id of the user who is trying to submit.
 * 		$identifier - The identifier of the rating-post.
 * 		$config     - The path to this file.
 * The keys of the rating-file have to contain the module-names.
 * An array needs to contain 2 elements:
 * 		table     - the table to be checked.
 * 		condition - a where-condition for CI's query-builder.
 * It will be checked in the following way:
 * 		If the index for the module is not set the rate will be submitted.
 * 		Else a $query = $this->db->get_where(table, condition); will be built.
 * 		If $query->num_rows(); does not return 0 the rating will NOT be
 * 		submitted. 
 * 		
 * An example:
 *
 * $config['comments'] = array(
 * 		'table'     => 'comments',
 * 		'condition' => array(
 * 			'user_id' => $userid,
 * 			'id'      => $identifier
 * 		)
 * );
 *
 * If a rate will be submitted for the module "modulename" the table
 * comments will be checked if there is a comment with the id $identifier
 * is written and the user_id in this row is $userid.
 * If this does not return 0; the rate will not be submitted.
 * Else it will.
 */
$config = array();
$config['comments'] = array(
	'table'     => 'comments',
	'condition' => array(
		'user_id' => $userid,
		'id'      => $identifier
	)
);
$config['blog'] = array(
	'table'     => 'blog',
	'condition' => array(
		'user_id' => $userid,
		'id'      => $identifier
	)
);