<?php
/** Product-owned native contract regressions, using the shared engine harness. */
require SPELLBOOK_TESTS . '/conformance/fixtures/bundled-feed-descriptors.php';
spellbook_test_declare( 'gf-form-notices' );
$target = array( 'id' => 11, 'fields' => array() );
$notice_resource = feed_resource( $target );
$notice_input    = array(
	'name'                 => 'Holiday closure',
	'startDate'            => '2026-12-24',
	'endDate'              => '2026-12-26',
	'advanceDays'          => 3,
	'message'              => '<p>Closed until {end_date:F j}.</p>',
	'disableDefaultStyles' => false,
);
$notice          = prepare_feed( 'gf-form-notices', $notice_input, $notice_resource );
check( ! is_wp_error( $notice ) && $notice['feed_name'] === 'Holiday closure' && $notice['advance_days'] === '3' && $notice['disable_default_styles'] === '0', 'Notice scalar values use exact native keys and encodings.' );
check( $notice['start_date'] === '2026-12-24' && $notice['message'] === $notice_input['message'], 'Notice dates and merge tags survive unchanged.' );
$notice['opaque'] = 'DO_NOT_DISCLOSE';
$notice_resource  = feed_resource( $target, $notice, 'update' );
foreach ( array(
	array( 'startDate' => '2027-01-01' ),
	array( 'startDate' => '2026-02-30' ),
	array( 'endDate' => '*-12-26' ),
	array( 'advanceDays' => -1 ),
	array( 'advanceDays' => '3' ),
	array( 'disableDefaultStyles' => 'false' ),
	array( 'message' => '' ),
	array( 'message' => '<script>alert(1)</script>' ),
	array( 'container_markup' => 'raw setting' ),
) as $patch ) {
	check( is_wp_error( prepare_feed( 'gf-form-notices', $patch, $notice_resource ) ), 'Reject invalid notice patch: ' . json_encode( $patch ) ); }
$schema_error = prepare_feed( 'gf-form-notices', array( 'name' => 5 ), $notice_resource );
$schema_refusal = is_wp_error( $schema_error ) ? Spellbook_Assistant_Refusal::of( $schema_error ) : null;
check( $schema_error->get_error_code() === 'spellbook_assistant_settings' && $schema_refusal['items'][0]['reason'] === 'invalid_value' && $schema_refusal['items'][0]['setting']['key'] === 'name', 'Schema-invalid feed settings return the descriptor\'s refusal, naming the setting, before the product runs.' );
$notice_updated = prepare_feed( 'gf-form-notices', array( 'disableDefaultStyles' => true ), $notice_resource );
check( $notice_updated['opaque'] === 'DO_NOT_DISCLOSE' && $notice_updated['disable_default_styles'] === '1', 'Notice partial update preserves unrelated native values.' );
$notice_read = feed_test_read( 'gf-form-notices', $notice_resource );
check( strpos( json_encode( $notice_read ), 'DO_NOT_DISCLOSE' ) === false, 'Notice descriptor does not leak unknown settings.' );
$notice_text = $notice_read['schema']['properties'];
check( $notice_text['name']['maxLength'] === 200 && $notice_text['name']['minLength'] === 1 && $notice_text['message']['maxLength'] === 4000 && $notice_text['message']['minLength'] === 1, 'Notice name and message are required bounded text.' );
check( ! is_wp_error( prepare_feed( 'gf-form-notices', $notice_read['values'], feed_resource( $target, $notice, 'activate' ) ) ), 'A complete reviewed notice is ready for activation.' );
foreach ( array(
	array( 'gf-form-notices', $notice, 'advance_days', 'invalid', 'advanceDays' ),
	array( 'gf-form-notices', $notice, 'disable_default_styles', 'invalid', 'disableDefaultStyles' ),
) as $fixture ) {
	$fixture[1][ $fixture[2] ] = $fixture[3];
	$read                      = feed_test_read( $fixture[0], feed_resource( $target, $fixture[1], 'inspect' ) );
	check( $read['outside'] === array( $fixture[4] => $fixture[3] ) && ! isset( $read['values'][ $fixture[4] ] ), 'A saved ' . $fixture[2] . ' outside the contract is marked as present, not read as no value.' );
	check( is_wp_error( feed_test_write( $fixture[0], $read['values'], feed_resource( $target, $fixture[1], 'activate' ) ) ), 'Omitting an unsupported saved ' . $fixture[2] . ' does not grant activation permission.' );
	$label    = Spellbook_Assistant_Settings::definitions()[ $fixture[0] ]['settings'][ $fixture[4] ]['label'];
	$replaced = feed_test_write( $fixture[0], array( $fixture[4] => $read['values'] ? reset( $read['values'] ) : null ), feed_resource( $target, $fixture[1], 'update' ) );
	check( is_wp_error( $replaced ) && strpos( $replaced->get_error_message(), $label ) !== false, 'A review never replaces a saved ' . $fixture[2] . ' it cannot show; the refusal names the setting.' );
}
