<?php// To Do: Assign a static variable to allow for multiple forms on the same page to be submitted through ajax// Add Shortcode ( [yikes-mailchimp] )function process_mailchimp_shortcode( $atts ) {			$text_domain = 'yikes-inc-easy-mailchimp-extender';		// Attributes	extract( shortcode_atts(		array(			'form' => '',			'submit' => 'Submit',			'title' => '0',			'description' => '0',			'ajax' => '',		), $atts , 'yikes-mailchimp' )	);			/* If the user hasn't authenticated yet, lets kill off */	if( get_option( 'yikes-mc-api-validation' , 'invalid_api_key' ) != 'valid_api_key' ) {		return '<div class="invalid-api-key-error"><p>' . __( "Whoops, you're not connected to MailChimp. You need to enter a valid MailChimp API key." , $text_domain ) . '</p></div>';	}		// if the user forgot to specify a form ID, lets kill of and warn them.	if( !$form ) {		return __( 'Whoops, it looks like you forgot to specify a form to display.', $text_domain );	}		global $wpdb;	// return it as an array, so we can work with it to build our form below	$form_results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'yikes_easy_mc_forms WHERE id = ' . $form . '', ARRAY_A );		// confirm we have some results, or return an error	if( !$form_results ) {		return __( "Oh no...This form doesn't exist. Head back to the manage forms page and select a different form." , $text_domain );	}		/*	*	Check if the user wants to use reCAPTCHA Spam Prevention	*/	if( get_option( 'yikes-mailchimp-recaptcha-status' , '' ) == '1' ) {		// if either of the Private the Secret key is left blank, we should display an error back to the user		if( get_option( 'yikes-mailchimp-recaptcha-site-key' , '' ) == '' ) {			return __( "Whoops! It looks like you enabled reCAPTCHA but forgot to enter the reCAPTCHA site key!" , $text_domain ) . '<span class="edit-link" style="display:block;margin-top:.5em;"><a class="post-edit-link" href="' . admin_url( 'admin.php?page=yikes-inc-easy-mailchimp-settings&section=recaptcha-settings' ) . '" title="' . __( 'ReCaptcha Settings' , $text_domain ) . '">' . __( 'Edit ReCaptcha Settings' , $text_domain ) . '</a></span>';		}		if( get_option( 'yikes-mailchimp-recaptcha-secret-key' , '' ) == '' ) {			return __( "Whoops! It looks like you enabled reCAPTCHA but forgot to enter the reCAPTCHA secret key!" , $text_domain ) . '<span class="edit-link" style="display:block;margin-top:.5em;"><a class="post-edit-link" href="' . admin_url( 'admin.php?page=yikes-inc-easy-mailchimp-settings&section=recaptcha-settings' ) . '" title="' . __( 'ReCaptcha Settings' , $text_domain ) . '">' . __( 'Edit ReCaptcha Settings' , $text_domain ) . '</a></span>';		}		// enqueue Google recaptcha JS		wp_register_script( 'google-recaptcha-js' , 'https://www.google.com/recaptcha/api.js' , array( 'jquery' ) , 'all' );		wp_enqueue_script( 'google-recaptcha-js' );		$recaptcha_site_key = get_option( 'yikes-mailchimp-recaptcha-site-key' , '' );		$recaptcha_box = '<div name="g-recaptcha" class="g-recaptcha" data-sitekey="' . $recaptcha_site_key . '"></div>';	}		// place our results into a seperate variable for easy looping	$form_data = $form_results[0];		// store our variables	$form_id = $form_data['id']; // form id (the id of the form in the database)	$list_id = $form_data['list_id']; // associated list id (users who fill out the form will be subscribed to this list)	$form_name = $form_data['form_name']; // form name	$form_description = stripslashes( $form_data['form_description'] );	$fields = json_decode( stripslashes( $form_data['fields'] ) , true );	$styles = json_decode( stripslashes( $form_data['custom_styles'] ) , true );	$send_welcome = $form_data['send_welcome_email'];	$redirect_user = $form_data['redirect_user_on_submit'];	$redirect_page = $form_data['redirect_page'];	$submission_settings = json_decode( stripslashes( $form_data['submission_settings'] ) , true );	$optin_settings = json_decode( stripslashes( $form_data['optin_settings'] ) , true );	$error_messages = json_decode( stripslashes( $form_data['error_messages'] ) , true );		$notifications = isset( $form_data['custom_notifications'] ) ? json_decode( stripslashes( $form_data['custom_notifications'] ) , true ) : '';		// enqueue the form styles	wp_enqueue_style( 'yikes-inc-easy-mailchimp-public-styles', YIKES_MC_URL . 'public/css/yikes-inc-easy-mailchimp-extender-public.min.css' );		// object buffer 	ob_start();				/* If the current user is logged in, and an admin...lets display our 'Edit Form' link */	if( is_user_logged_in() ) {		if( current_user_can( apply_filters( 'yikes-mailchimp-user-role-access' , 'manage_options' ) ) ) {			$edit_form_link = '<span class="edit-link">';				$edit_form_link .= '<a class="post-edit-link" href="' . admin_url( 'admin.php?page=yikes-mailchimp-edit-form&id=' . $form ) . '" title="' . __( 'Edit' , $text_domain ) . ' ' . ucwords( $form_name ) . '">' . __( 'Edit Form' , $text_domain ) . '</a>';			$edit_form_link .= '</span>';		} else {			$edit_form_link = '';		}	}			// ensure there is an 'email' field the user can fill out	// or else MailChimp throws errors at you		// extract our array keys		if( isset( $fields ) && !empty( $fields ) ) {				$array_keys = array_keys( $fields );			// check for EMAIL in that array			if( !in_array( 'EMAIL' , $array_keys ) ) {				return '<p>' . __( "An email field is required for all MailChimp forms. Please add an email field to this form." , $text_domain ) . '</p><p>' . $edit_form_link . '</p>';			}		} else {			$error = '<p>' . __( "Whoops, it looks like you forgot to assign fields to this form." , $text_domain ) . '</p>';			if( is_user_logged_in() ) {				if( current_user_can( apply_filters( 'yikes-mailchimp-user-role-access' , 'manage_options' ) ) ) {					return $error . $edit_form_link;				}			} else {				return $error;			}		}		/*	*  pre-form action hooks	*  check readme for usage examples	*/	do_action( 'yikes-mailchimp-before-form-'.$form_id );	do_action( 'yikes-mailchimp-before-form' );			// used to hide the form, keep values in the form etc.	$form_submitted = 0;		// display the form description if the user 	// has specified to do so	if( !empty( $title ) && $title == 1 ) {		echo '<h3 class="yikes-mailchimp-form-title yikes-mailchimp-form-title-'.$form_id.'">' . apply_filters( 'yikes-mailchimp-form-title-'.$form_id , apply_filters( 'the_title' , $form_name ) ) . '</h3>';	}		// display the form description if the user 	// has specified to do so	if( !empty( $description ) && $description == 1 ) {		echo '<p class="yikes-mailchimp-form-description yikes-mailchimp-form-description-'.$form_id.'">' . apply_filters( 'yikes-mailchimp-form-description-'.$form_id ,  $form_description ) . '</p>';	}		// Check for AJAX	if( ( !empty( $atts['ajax'] ) && $atts['ajax'] == 1 ) || $submission_settings['ajax'] == 1 ) {		// Include our ajax processing class		// require_once( YIKES_MC_PATH . 'public/partials/ajax/class.public_ajax.php' );		// enqueue our ajax script		wp_register_script( 'yikes-easy-mc-ajax' , YIKES_MC_URL . 'public/js/yikes-mc-ajax-forms.min.js' , array( 'jquery' ) , $text_domain, false );		wp_localize_script( 'yikes-easy-mc-ajax' , 'object' , array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'list_id' => $list_id , 'optin_settings' => json_encode( $optin_settings ), 'submission_settings' => json_encode( $submission_settings ), 'error_messages' => json_encode( $error_messages ), 'notifications' => json_encode( $notifications ), 'form_id' => $form_id ) );		wp_enqueue_script( 'yikes-easy-mc-ajax' );	}		/*	*	On form submission, lets include our form processing file	*	- processes both ajax and non-ajax forms	*/	if( isset( $_POST ) && !empty( $_POST ) && $submission_settings['ajax'] == 0 ) {		// lets include our form processing file		include_once( YIKES_MC_PATH . 'public/partials/shortcodes/process/process_form_submission.php' );	}		// render the form!	?>		<form id="<?php echo sanitize_title( $form_name ); ?>" class="yikes-easy-mc-form yikes-easy-mc-form-<?php echo $form_id; ?>" action="" method="POST" <?php if( !empty( $_POST ) && $form_submitted == 1 && $submission_settings['hide_form_post_signup'] == 1 ) { echo 'style="display:none;"'; } ?>>									<?php 			foreach( $fields as $field ) {										// input array					$field_array = array();					// label array					$label_array = array();										if( $field['additional-classes'] != '' ) {						$custom_classes = explode( ', ' , $field['additional-classes'] );						// check our custom class array for field-left/field-right						// if it's set we need to assign it to our label and remove it from the field classes						 // input half left						if( in_array( 'field-left-half' , $custom_classes ) ) {							$label_array['class'] = 'class="field-left-half"';							$key = array_search( 'field-left-half' , $custom_classes );							unset( $custom_classes[$key] );						} // input half right						if( in_array( 'field-right-half' , $custom_classes ) ) {							$label_array['class'] = 'class="field-right-half"';							$key = array_search( 'field-right-half' , $custom_classes );							unset( $custom_classes[$key] );						} // input third left						if( in_array( 'field-left-third' , $custom_classes ) ) {							$label_array['class'] = 'class="field-left-third"';							$key = array_search( 'field-left-half' , $custom_classes );							unset( $custom_classes[$key] );						} // input third right						if( in_array( 'field-right-third' , $custom_classes ) ) {							$label_array['class'] = 'class="field-right-third"';							$key = array_search( 'field-right-half' , $custom_classes );							unset( $custom_classes[$key] );						} // 2 column radio						if( in_array( 'option-2-col' , $custom_classes ) ) {							$label_array['class'] = 'class="option-2-col"';							$key = array_search( 'option-2-col' , $custom_classes );							unset( $custom_classes[$key] );						} // 3 column radio						if( in_array( 'option-3-col' , $custom_classes ) ) {							$label_array['class'] = 'class="option-3-col"';							$key = array_search( 'option-3-col' , $custom_classes );							unset( $custom_classes[$key] );						} // 4 column radio						if( in_array( 'option-4-col' , $custom_classes ) ) {							$label_array['class'] = 'class="option-4-col"';							$key = array_search( 'option-4-col' , $custom_classes );							unset( $custom_classes[$key] );						} // inline radio & checkboxes etc						if( in_array( 'option-inline' , $custom_classes ) ) {							$label_array['class'] = 'class="option-inline"';							$key = array_search( 'option-inline' , $custom_classes );							unset( $custom_classes[$key] );						}					} else {						$custom_classes = array();					}										if( isset( $field['hide-label'] ) ) {						if( $field['hide-label'] == 1 ) {							$custom_classes[] = 'field-no-label';						}					}								/* Store tag variable based on field type */				if( isset( $field['merge'] ) ) {					$tag = 'merge';				} else {					$tag = 'group_id';				}								// build up our array				$field_array['id'] = 'id="' . $field[$tag] . '" ';				$field_array['name'] = 'name="' . $field[$tag] . '" ';				$field_array['placeholder'] = isset( $field['placeholder'] ) ? 'placeholder="' . stripslashes( $field['placeholder'] ) . '" ' : '';				$field_array['classes'] = 'class="yikes-easy-mc-'.$field['type'] . ' ' .  trim( implode( ' ' , $custom_classes ) ) . '" ';									// email must always be required and visible				if( $field['type'] == 'email' ) {					$field_array['required'] = 'required="required"';					$label_array['visible'] = '';				} else {					$field_array['required'] = isset( $field['require'] ) ? 'required="required"' : '';					$label_array['visible'] = isset( $field['hide'] ) ? 'style="display:none;"' : '';				}								/* Loop Over Standard Fields (aka merge variables) */				if( isset( $field['merge'] ) ) {																			// print_r( $field );										// loop over our fields by Type					switch ( $field['type'] ) {												default:						case 'email':						case 'text':						case 'number':																	// pass our default value through our filter to parse dynamic data by tag (used solely for 'text' type)							$default_value = apply_filters( 'yikes-mailchimp-process-default-tag' , $field['default'] );														if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}								?>								<input <?php echo implode( ' ' , $field_array ); if( $field['type'] != 'email' && $field['type'] != 'number' ) { ?> type="text" <?php } else if( $field['type'] == 'email' ) { ?> type="email" <?php } else { ?> type="number" <?php } ?> value="<?php if( !empty( $_POST ) && $form_submitted != 1 ) { echo $_POST[$field['merge']]; } else { echo $default_value; } ?>">																<!-- description -->								<?php if( isset( $field['description'] ) ) { ?><p class="form-field-description" <?php if( !empty( $_POST ) && $form_submitted == 1 && $submission_settings['hide_form_post_signup'] == 1 ) { echo 'style="display:none;"'; } ?>><small><?php echo stripslashes( $field['description'] ); ?></small></p><?php } ?>																<?php							if( !isset( $field['hide-label'] ) ) {								?>								</label>								<?php							}							break;												case 'url':						case 'imageurl':							$default_value = $field['default'];															if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}								?>								<input <?php echo implode( ' ' , $field_array ); ?> type="url" <?php if( $field['type'] == 'url' ) { ?> title="<?php _e( 'Please enter a valid URL to the website.' , $text_domain ); ?>" <?php } else { ?> title="<?php _e( 'Please enter a valid URL to the image.' , $text_domain ); ?>" <?php } ?> value="<?php if( !empty( $_POST ) && $form_submitted != 1 ) { echo $_POST[$field['merge']]; } else { echo $default_value; } ?>">																<!-- description -->								<?php if( isset( $field['description'] ) ) { ?><p class="form-field-description" <?php if( !empty( $_POST ) && $form_submitted == 1 && $submission_settings['hide_form_post_signup'] == 1 ) { echo 'style="display:none;"'; } ?>><small><?php echo stripslashes( $field['description'] ); ?></small></p><?php } ?>																<?php							if( !isset( $field['hide-label'] ) ) {								?>								</label>								<?php							}						break;												case 'phone':							$default_value = $field['default'];							$phone_format = $field['phone_format'];									?>								<script type="text/javascript">									/* Replace incorrect values and format it correctly for MailChimp API */									function formatUSPhoneNumber( e ) {										var number = e.value;										var new_phone_number = number.trim().replace( '(' , '' ).replace( ')', '-' ).replace(/(\d\d\d)(\d\d\d)(\d\d\d\d)/, "$1-$2-$3");										jQuery( '.<?php echo "yikes-easy-mc-".$field['type']; ?>' ).val( new_phone_number );									}								</script>							<?php							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}								?>								<input <?php echo implode( ' ' , $field_array ); ?> type="text" <?php if( $phone_format != 'US' ) { ?>  title="<?php _e( 'International Phone number (eg:1-541-754-3010)' , $text_domain ); ?>" pattern="<?php echo apply_filters( 'yikes-mailchimp-international-phone-pattern' , '[0-9]' ); ?>" <?php } else { ?> title="<?php _e( 'US Phone Number (ie: 123-456-7890)' , $text_domain ); ?>" pattern="<?php echo apply_filters( 'yikes-mailchimp-international-phone-pattern' , '^(\([0-9]{3}\)|[0-9]{3}-)[0-9]{3}-[0-9]{4}$' ); ?>" onblur="formatUSPhoneNumber(this);"<?php } ?> value="<?php if( !empty( $_POST ) && $form_submitted != 1 ) { echo $_POST[$field['merge']]; } else { echo $default_value; } ?>">																<!-- description -->								<?php if( isset( $field['description'] ) ) { ?><p class="form-field-description" <?php if( !empty( $_POST ) && $form_submitted == 1 && $submission_settings['hide_form_post_signup'] == 1 ) { echo 'style="display:none;"'; } ?>><small><?php echo stripslashes( $field['description'] ); ?></small></p><?php } ?>																<?php							if( !isset( $field['hide-label'] ) ) {								?>								</label>								<?php							}						break;												case 'zip':							$default_value = $field['default'];							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}								?>								<input <?php echo implode( ' ' , $field_array ); ?> type="text" pattern="\d{5,5}(-\d{4,4})?" title="<?php _e( '5 digit zip code, numbers only' , $text_domain ); ?>" value="<?php if( !empty( $_POST ) && $form_submitted != 1 ) { echo $_POST[$field['merge']]; } else { echo $default_value; } ?>">																<!-- description -->								<?php if( isset( $field['description'] ) ) { ?><p class="form-field-description" <?php if( !empty( $_POST ) && $form_submitted == 1 && $submission_settings['hide_form_post_signup'] == 1 ) { echo 'style="display:none;"'; } ?>><small><?php echo stripslashes( $field['description'] ); ?></small></p><?php } ?>																<?php							if( !isset( $field['hide-label'] ) ) {								?>								</label>								<?php							}						break;												case 'address':							// default?							$default_value = $field['default'];							// required fields							$required_fields = array( 'addr1' => 'address' , 'addr2' => 'address 2', 'city' => 'city', 'state' =>'state', 'zip' =>'zip' , 'country' => 'country' );							// store empty number for looping							$x = 0;							// hidden labels							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}																foreach( $required_fields as $type => $label ) {									$field_array['name'] = 'name="'.$field[$tag].'['.$type.']'.'"';									switch( $type ) {																				default:										case 'addr1':										case 'addr2':										case 'city':											?>												<label>													<input <?php echo implode( ' ' , $field_array ); ?> type="text"  value="<?php if( !empty( $_POST ) && $form_submitted != 1 ) { echo $_POST[$field['merge']]; } else { echo $default_value; } ?>">													<span style="text-align:right;"><small><?php echo $label; ?></small></span>												</label>											<?php										break;																				case 'state':											?>												<label>													<select <?php echo implode( ' ' , $field_array ); ?>>														<?php include_once( YIKES_MC_PATH . 'public/partials/shortcodes/templates/state-dropdown.php' ); ?>													</select>													<span style="text-align:right;"><small><?php echo $label; ?></small></span>												</label>											<?php										break;																				case 'zip':											/*												Regex to validate US zip codes												\d{5,5}(-\d{4,4})?											*/											?>												<label>													<input <?php echo implode( ' ' , $field_array ); ?> type="text" pattern="\d{5,5}(-\d{4,4})?" title="<?php _e( '5 digit zip code, numbers only' , $text_domain ); ?>" value="<?php if( !empty( $_POST ) && $form_submitted != 1 ) { echo $_POST[$field['merge']]; } else { echo $default_value; } ?>">													<span style="text-align:right;"><small><?php echo $label; ?></small></span>												</label>											<?php										break;																				case 'country':											?>												<label>													<select <?php echo implode( ' ' , $field_array ); ?>>														<?php include_once( YIKES_MC_PATH . 'public/partials/shortcodes/templates/country-dropdown.php' ); ?>													</select>													<span style="text-align:right;"><small><?php echo $label; ?></small></span>												</label>											<?php										break;									}								}																// description								if( isset( $field['description'] ) && trim( $field['description'] ) != '' ) { ?><p class="form-field-description"><small><?php echo trim( stripslashes( $field['description'] ) ); ?></small></p><?php }																if( !isset( $field['hide-label'] ) ) {								?>									</label>								<?php								}															break;														case 'date':						case 'birthday':													// bootstrap datepicker requirements							wp_enqueue_script( 'bootstrap-hover-dropdown' , 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-hover-dropdown/2.0.10/bootstrap-hover-dropdown.min.js' , array( 'jquery' ) );							wp_enqueue_script( 'bootstrap-datepicker-script' , 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/js/bootstrap-datepicker.min.js' , array( 'jquery' , 'bootstrap-hover-dropdown' ) );							wp_enqueue_style( 'bootstrap-datepicker-styles' , 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/css/bootstrap-datepicker3.standalone.min.css' );							wp_enqueue_style( 'override-datepicker-styles' , YIKES_MC_URL . 'public/css/yikes-inc-easy-mailchimp-datepicker-styles.css' , array( 'bootstrap-datepicker-styles' ) );														$date_format = $field['date_format'];							// initialize the datepicker							?>								<style>									.datepicker-dropdown {										width: 20% !important;										margin-top: 35px;									}								</style>								<script type="text/javascript">									jQuery(document).ready(function() {										jQuery('input[data-attr-type="<?php echo $field['type']; ?>"]').datepicker({											format : '<?php echo $date_format; ?>'										});									});								</script>								<?php														$default_value = $field['default'];							// store empty number for looping							$x = 0;														// hidden labels							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}															?>								<input <?php echo implode( ' ' , $field_array ); ?> type="text" <?php if( $field['type'] == 'date' ) { ?> data-attr-type="date" <?php } else { ?> data-attr-type="birthday" <?php } ?> value="<?php if( !empty( $_POST ) && $form_submitted != 1 ) { echo $_POST[$field['merge']]; } else { echo $default_value; } ?>">														<?php							if( !isset( $field['hide-label'] ) ) {								?>									</label>								<?php							}						break;													case 'dropdown':							$default_value = $field['default_choice'];							// store empty number for looping							$x = 0;							// hidden labels							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}								?>									<select <?php echo implode( ' ' , $field_array ); ?>>										<?php 												// decode for looping											$choices = json_decode( $field['choices'] , true );											foreach( $choices as $choice ) {												?><option value="<?php echo $choice; ?>" <?php selected( $default_value , $x ); ?>><?php echo stripslashes( $choice ); ?></option><?php												$x++;											} 										?>									</select>																<!-- description -->								<?php if( isset( $field['description'] ) && trim( $field['description'] ) != '' ) { ?><p class="form-field-description"><small><?php echo trim( stripslashes( $field['description'] ) ); ?></small></p><?php }																if( !isset( $field['hide-label'] ) ) {								?>									</label>								<?php								}											break;													case 'radio':						case 'checkbox':							// remove the ID (as to not assign the same ID to every radio button)							unset( $field_array['id'] );							$choices = json_decode( $field['choices'] , true );							// assign a default choice							$default_value = isset( $field['default_choice'] ) ? $field['default_choice'] : $choices[0];							// if the form was submit, but failed, let's reset the post data							if( !empty( $_POST ) && $form_submitted != 1 ) {								$default_value = $_POST[$field['merge']];							}							$count = count( $choices );							$i = 1;							$x = 0;														// hidden labels							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['merge']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['merge'] . '-label'; ?> checkbox-parent-label"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}														foreach( $choices as $choice ) {									?>									<label for="<?php echo $field['merge'] . '-' . $i; ?>" class="yikes-easy-mc-checkbox-label <?php echo implode( ' ' , $custom_classes ); if( $i === $count ) { ?>last-selection<?php } ?>">										<input type="<?php echo $field['type']; ?>" name="<?php echo $field['merge']; ?>" id="<?php echo $field['merge'] . '-' . $i; ?>" <?php checked( $default_value , $choice ); ?> value="<?php echo $choice; ?>">										<span class="<?php echo $field['merge'] . '-label'; ?>"><?php echo stripslashes( $choice ); ?></span>									</label>									<?php									$i++;									$x++;								}																// description								if( isset( $field['description'] ) && trim( $field['description'] ) != '' ) { ?><p class="form-field-description"><small><?php echo trim( stripslashes( $field['description'] ) ); ?></small></p><?php }																							// close label								if( !isset( $field['hide-label'] ) ) {								?>									</label>								<?php								}							break;										}									} else { // loop over interest groups									// store default choice					$default_choice = isset( $field['default_choice'] ) ? $field['default_choice'] : array();										// if the form was submit, but failed, let's reset the post data					if( !empty( $_POST ) && $form_submitted != 1 ) {						$default_value = $default_choice;					}					// get our groups					$groups = json_decode( $field['groups'] , true );					$count = count( $groups );										if( $field['type'] == 'checkboxes' ) {						$type = 'checkbox';					} else if( $field['type'] == 'radio' ) {						$type = 'radio';					}										// loop over the interest group field types					switch ( $field['type'] ) {												case 'checkboxes':						case 'radio':							$i = 0; // used to select our checkboxes/radios							$x = 1; // used to find the last item of our array														// hidden labels							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['group_id']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['group_id'] . '-label'; ?> checkbox-parent-label"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}									foreach( $groups as $group ) {										?>										<label for="<?php echo $field['group_id'] . '-' . $i; ?>" class="yikes-easy-mc-checkbox-label <?php echo implode( ' ' , $custom_classes ); if( $x === $count ) { ?>last-selection<?php } ?>">											<input type="<?php echo $type; ?>" name="<?php echo $field['group_id']; ?>[]" id="<?php echo $field['group_id'] . '-' . $i; ?>" <?php if( $field['type'] == 'checkboxes' ) { if( in_array( $i , $default_choice ) ) { echo 'checked="checked"'; } } else { checked( $default_choice , $i ); } ?> value="<?php echo $group['name']; ?>">											<?php echo stripslashes( $group['name'] ); ?>										</label>										<?php										$i++;										$x++;									}																			// description									if( isset( $field['description'] ) && trim( $field['description'] ) != '' ) { ?><p class="form-field-description"><small><?php echo trim( stripslashes( $field['description'] ) ); ?></small></p><?php } 																		// close label								if( !isset( $field['hide-label'] ) ) {								?>									</label>								<?php								}							break;											case 'dropdown':														// hidden labels							if( !isset( $field['hide-label'] ) ) {								?>								<label for="<?php echo $field['group_id']; ?>" <?php echo implode( ' ' , $label_array ); ?>><span class="<?php echo $field['group_id'] . '-label'; ?>"><?php echo stripslashes( $field['label'] ); ?></span>								<?php							}																?>									<select <?php echo implode( ' ' , $field_array ); ?>>										<?php 												$i = 0;											foreach( $groups as $group ) {												?><option <?php selected( $i , $default_choice ); ?> value="<?php echo $i; ?>"><?php echo stripslashes( $group['name'] ); ?></option><?php												$i++;											} 										?>									</select>									<?php if( isset( $field['description'] ) && trim( $field['description'] ) != '' ) { ?><p class="form-field-description"><small><?php echo trim( stripslashes( $field['description'] ) ); ?></small></p><?php } ?>															<?php								// hidden labels								if( !isset( $field['hide-label'] ) ) {									?>									</label>									<?php								}							break;												}				} // end interest groups			}						/* if we've enabled reCaptcha protection */			if( isset( $recaptcha_box ) ) {				echo $recaptcha_box;			}						do_action( 'yikes-mailchimp-additional-form-fields-'.$form_id );			do_action( 'yikes-mailchimp-additional-form-fields' );							?>									<!-- Submit Button -->			<?php echo apply_filters( 'yikes-easy-mc-submit-button' , '<input type="submit" value="' . stripslashes( $submit ) . '" class="yikes-easy-mc-submit-button yikes-easy-mc-submit-button-' . $form_data['id'] . '">' ); ?>			<!-- Nonce Security Check -->			<?php wp_nonce_field( 'yikes_easy_mc_form_submit', 'yikes_easy_mc_new_subscriber' ); ?>		</form>		<!-- MailChimp Form generated using Easy MailChimp Forms by Yikes Inc. (https://wordpress.org/plugins/yikes-inc-easy-mailchimp-extender/) -->			<?php		/* If the current user is logged in, and an admin...lets display our 'Edit Form' link */		if( is_user_logged_in() ) {			if( current_user_can( apply_filters( 'yikes-mailchimp-user-role-access' , 'manage_options' ) ) ) {				?>					<span class="edit-link yikes-easy-mailchimp-edit-form-link">						<a class="post-edit-link" href="<?php echo admin_url( 'admin.php?page=yikes-mailchimp-edit-form&id=' . $form ); ?>" title="<?php echo __( 'Edit' , $text_domain ) . ' ' . ucwords( $form_name ); ?>"><?php _e( 'Edit Form' , $text_domain ); ?></a>					</span>				<?php			}		}			/*	*  post-form action hooks	*  check readme for usage examples	*/	do_action( 'yikes-mailchimp-after-form-'.$form_id );	do_action( 'yikes-mailchimp-after-form' );			/*	*	Update the impressions count	*	for non-admins	*/	if( !current_user_can( 'manage_options' ) ) {		$form_data['impressions']++;		$wpdb->update( 			$wpdb->prefix . 'yikes_easy_mc_forms',				array( 					'impressions' => $form_data['impressions'],				),				array( 'ID' => $form ), 				array(					'%d',	// send welcome email				), 				array( '%d' ) 			);	}		// you call *your*:	return ob_get_clean();	}add_shortcode( 'yikes-mailchimp', 'process_mailchimp_shortcode' ); ?>