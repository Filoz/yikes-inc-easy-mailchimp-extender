<?php/** * Options page for rendering checkboxes * * Page template that houses all of the checkbox settings. * * @since 6.0.0 * * @package WordPress * @subpackage Component*/			// enqueue jquery qtip for our tooltip	wp_enqueue_script( 'jquery-qtip-tooltip' , 'https://cdnjs.cloudflare.com/ajax/libs/qtip2/2.2.1/jquery.qtip.js' , array( 'jquery' ) );	wp_enqueue_style( 'jquery-qtip-style' , 'https://cdnjs.cloudflare.com/ajax/libs/qtip2/2.2.1/jquery.qtip.min.css' );		?>		<script>			jQuery( document ).ready( function() {				jQuery( '.dashicons-editor-help' ).each(function() {					 jQuery(this).qtip({						 content: {							 text: jQuery(this).next('.tooltiptext'),							 style: { 								def: false							 }						 }					 });				 });				 jQuery( '.qtip' ).each( function() {					jQuery( this ).removeClass( 'qtip-default' );				 });			});		</script>		<?php		// active plugins array	// defaults: comments / registration	$active_plugins = array(		'comment_form' => __( 'WordPress Comment Form', $this->text_domain ),		'registration_form' => __( 'WordPress Registration Form', $this->text_domain )	);		$class_descriptions = array(		'comment_form' => '<img class="tooltip-integration-banner" src="' . YIKES_MC_URL . 'includes/images/Checkbox_Integration_Logos/wordpress-banner-logo.png" title="' . __( 'WordPress' , $this->text_domain ) . '">' . __( 'Enabling the comment form opt-in checkbox will display a checkbox to your current users when leaving a comment.' , $this->text_domain ),		'registration_form' => '<img class="tooltip-integration-banner" src="' . YIKES_MC_URL . 'includes/images/Checkbox_Integration_Logos/wordpress-banner-logo.png" title="' . __( 'WordPress' , $this->text_domain ) . '">' . __( 'Enabling the registration form opt-in checkbox will display a checkbox to new users when registering for your site.' , $this->text_domain ),		'woocommerce_checkout_form' => '<img class="tooltip-integration-banner" src="' . YIKES_MC_URL . 'includes/images/Checkbox_Integration_Logos/woocommerce-banner.png" title="' . __( 'WooCommerce Store' , $this->text_domain ) . '">' . __( 'Enabling the WooCommerce checkout opt-in form allows you to capture email addresses from users who make purchases in your store. This option will add an opt-in checkbox to the checkout page.' , $this->text_domain ),		'easy_digital_downloads_checkout_form' => '<img class="tooltip-integration-banner" src="' . YIKES_MC_URL . 'includes/images/Checkbox_Integration_Logos/edd-banner.png" title="' . __( 'Easy Digital Downloads' , $this->text_domain ) . '">' . __( 'Enabling the Easy Digital Downloads checkout opt-in allows users who make a purchase to opt-in to your mailing list during checkout.' , $this->text_domain ),		'buddypress_form' => '<img class="tooltip-integration-banner" src="' . YIKES_MC_URL . 'includes/images/Checkbox_Integration_Logos/buddypress-banner.png" title="' . __( 'BuddyPress' , $this->text_domain ) . '">' . __( 'Enabling the BuddyPress opt-in allows users who register for your site to be automatically added to the mailing list of your choice.' , $this->text_domain ),		'bbpress_forms' => '<img class="tooltip-integration-banner" src="' . YIKES_MC_URL . 'includes/images/Checkbox_Integration_Logos/bbpress-banner.png" title="' . __( 'bbPress' , $this->text_domain ) . '">' . __( 'Enabling the bbPress opt-in enables users who register to use the forums on your site to be automatically added to the mailing list of your choice.' , $this->text_domain ),		'contact_form_7' => '<img class="tooltip-integration-banner" src="' . YIKES_MC_URL . 'includes/images/Checkbox_Integration_Logos/cf7-banner.png" title="' . __( 'Contact Form 7' , $this->text_domain ) . '">' . __( 'Once the Contact Form 7 integration is active you can use our custom [yikes_mailchimp_checkbox label="Custom Label Text"] in your contact forms to subscribe users to a pre-selected list.' , $this->text_domain ),	);			// Easy Digital Downloads	if( class_exists( 'Easy_Digital_Downloads' ) ) {		$active_plugins['easy_digital_downloads_checkout_form'] = __( 'Easy Digital Downloads Checkout', $this->text_domain );	}	// WooCommerce	if( class_exists( 'WooCommerce' ) ) {		$active_plugins['woocommerce_checkout_form'] = __( 'WooCommerce Checkout', $this->text_domain );	}	// BuddyPress	if( class_exists( 'BuddyPress' ) ) {		$active_plugins['buddypress_form'] = __( 'BuddyPress Registration', $this->text_domain );	}	// bbPress	if( class_exists( 'bbPress' ) ) {		$active_plugins['bbpress_forms'] = __( 'bbPress', $this->text_domain );	}	// Contact Form 7	if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {		$active_plugins['contact_form_7'] = __( 'Contact Form 7', $this->text_domain );	}		// store our checkbox options	$options = get_option( 'optin-checkbox-init' , '' );	?><h3><span><?php _e( 'Checkbox Settings' , $this->text_domain ); ?></span><?php echo $api_connection; ?></h3>	<?php		// lets confirm the user has a valid API key stored		if( $this->is_user_mc_api_valid_form( false ) == 'valid' ) {			/// Check for a transient, if not - set one up for one hour			if ( false === ( $list_data = get_transient( 'yikes-easy-mailchimp-list-data' ) ) ) {				// initialize MailChimp Class				$MailChimp = new MailChimp( get_option( 'yikes-mc-api-key' , '' ) );				// retreive our list data				$list_data = $MailChimp->call( 'lists/list' , array( 'apikey' => get_option( 'yikes-mc-api-key' , '' ) ) );				// set our transient				set_transient( 'yikes-easy-mailchimp-list-data', $list_data, 1 * HOUR_IN_SECONDS );			}		} else {			?>			<div class="inside">				<?php echo __( 'Please' , $this->text_domain ) . ' <a href="' . admin_url( 'admin.php?page=yikes-inc-easy-mailchimp-settings&section=general-settings' ) . '" title="' . __( 'General Settings' , $this->text_domain ) . '">' . __( 'enter a valid MailChimp API key' , $this->text_domain ) . '</a> to setup opt-in checkboxes.'; ?>			</div>			<?php			return;		}	?>	<div class="inside">			<p>		<?php _e( 'Select which plugins should integrate with Yikes Inc. Easy MailChimp. Depending on which plugins you choose to integrate with, an optin checkbox will be generated. For example, the comment form checkbox will generate a checkbox below the standard WordPress comment form to add commenters to a specific mailing list.' , $this->text_domain ); ?>	</p>			<!-- Settings Form -->	<form action='options.php' method='post'>				<?php settings_fields( 'yikes_inc_easy_mc_checkbox_settings_page' ); ?>		<ul style="display:inline-block;">		<?php			if( !empty( $active_plugins ) ) { 								foreach( $active_plugins as $class => $value ) {					// echo  $class;					$checked = isset( $options[$class]['value'] ) ? 'checked="checked"' : '';					$hidden =  !isset( $options[$class]['value'] ) ? 'style="display:none;"' : '';					$checkbox_label = isset( $options[$class]['label'] ) ? esc_attr__( $options[$class]['label'] ) : '';					$precheck_checkbox = isset( $options[$class]['precheck'] ) ? $options[$class]['precheck'] : '';					$selected_list = isset( $options[$class]['associated-list'] ) ? $options[$class]['associated-list'] : '-';					?>						<li>							<label>								<input type="checkbox" name="optin-checkbox-init[<?php echo $class; ?>][value]" <?php echo $checked; ?> onclick="jQuery(this).parents('li').next().stop().slideToggle();"><?php echo ucwords( $value ); ?><span class="dashicons dashicons-editor-help" style="font-size:15px;line-height:1.6;"></span><div class="tooltiptext qtip-bootstrap" style="display:none;"><?php echo $class_descriptions[$class]; ?></div>							</label>						</li>						<!-- checkbox settings, text - associated list etc. -->						<li class="optin-checkbox-init[<?php echo $class; ?>]-settings" <?php echo $hidden; ?>>							<p style="margin-top:0;padding-top:0;margin-bottom:0;padding-bottom:0;">								<!-- checkbox associated list -->								<label><?php _e( 'Associated List' , $this->text_domain ); ?>									<?php										if( $list_data['total'] > 0 ) {											?>												<select class="optin-checkbox-init[<?php echo $class; ?>][associated-list]" name="optin-checkbox-init[<?php echo $class; ?>][associated-list]" style="display:block;width:250px;">														<option value="-" <?php selected( $selected_list , '-' ); ?>><?php _e( 'Select a List' , $this->text_domain ); ?></option>													<?php foreach( $list_data['data'] as $list ) { ?>														<option value="<?php echo $list['id']; ?>" <?php selected( $selected_list , $list['id'] ); ?>><?php echo $list['name']; ?></option>													<?php } ?>												</select>											<?php										} else {											echo '<p class="description" style="padding: .5em 0 .5em 0;"><strong>' . __( 'You have not setup any lists. You should head over to MailChimp and setup your first list.' , $this->text_domain ) . '</strong></p>';										}									?>								</label>								<!-- checkbox text label -->								<label><?php _e( 'Checkbox Label' , $this->text_domain ); ?>									<input type="text" class="optin-checkbox-init[<?php echo $class; ?>][label]" name="optin-checkbox-init[<?php echo $class; ?>][label]" style="display:block;width:250px;" value="<?php echo $checkbox_label; ?>">								</label>								<!-- prechecked? -->								<label><?php _e( 'Precheck Checkbox' , $this->text_domain ); ?>									<select id="optin-checkbox-init[<?php echo $class; ?>][precheck]" name="optin-checkbox-init[<?php echo $class; ?>][precheck]" class="optin-checkbox-init[<?php echo $class; ?>][precheck]" style="display:block;width:250px;">										<option value="true" <?php selected( $precheck_checkbox , 'true' ); ?>><?php _e( 'Yes' , $this->text_domain ); ?></option>										<option value="false" <?php selected( $precheck_checkbox , 'false' ); ?>><?php _e( 'No' , $this->text_domain ); ?></option>									</select>								</label>							</p>							<br />						</li>					<?php				}			} else {				?>					<li>						<?php _e( 'Nothing is active.' , $this->text_domain ); ?>					</li>				<?php			}		?>	</ul>	<p class="description"><?php _e( 'This is currently being built out and will begin working in future releases.' , $this->text_domain ); ?></p>															<?php submit_button(); ?>										</form></div> <!-- .inside -->