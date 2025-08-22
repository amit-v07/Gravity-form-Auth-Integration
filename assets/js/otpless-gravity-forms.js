jQuery(document).ready(function($) {
    
    // Store form data temporarily
    let pendingFormData = null;
    let currentForm = null;
    
    // Initialize OTPless
    if (typeof OTPless !== 'undefined') {
        OTPless.init({
            clientId: otpless_ajax.client_id,
            redirectUri: otpless_ajax.redirect_uri
        });
    }
    
    // Intercept all Gravity Forms submissions
    function initializeOTPlessForms() {
        $('.gform_wrapper form').each(function() {
            const $form = $(this);
            const formId = $form.attr('id');
            const formIdNumber = formId ? formId.replace('gform_', '') : null;
            
            // Skip if already initialized
            if ($form.hasClass('otpless-initialized')) {
                return;
            }
            
            // Check if this form is enabled for OTPless
            const enabledForms = otpless_ajax.enabled_forms || [];
            if (enabledForms.length > 0 && !enabledForms.includes(parseInt(formIdNumber))) {
                console.log('Form ' + formIdNumber + ' not enabled for OTPless');
                return;
            }
            
            $form.addClass('otpless-initialized');
            
            // Intercept form submission
            $form.on('submit', function(e) {
                e.preventDefault();
                
                // Check if user is already authenticated
                if (isUserAuthenticated()) {
                    // User is authenticated, submit form normally
                    $form.off('submit'); // Remove our handler temporarily
                    $form.submit(); // Submit the form
                    return;
                }
                
                // Store form data and show authentication modal
                currentForm = $form;
                pendingFormData = $form.serialize();
                
                showOTPlessModal();
            });
            
            // Also intercept submit button clicks as backup
            const $submitButton = $form.find('input[type="submit"], button[type="submit"]');
            if ($submitButton.length) {
                $submitButton.on('click', function(e) {
                    e.preventDefault();
                    
                    // Check if user is already authenticated
                    if (isUserAuthenticated()) {
                        // User is authenticated, submit form normally
                        $form.off('submit'); // Remove our handler temporarily
                        $form.submit(); // Submit the form
                        return;
                    }
                    
                    // Store form data and show authentication modal
                    currentForm = $form;
                    pendingFormData = $form.serialize();
                    
                    showOTPlessModal();
                });
            }
        });
    }
    
    // Initialize forms on page load
    initializeOTPlessForms();
    
    // Initialize forms when Gravity Forms renders
    $(document).on('gform_post_render', function(event, formId, current_page) {
        setTimeout(function() {
            initializeOTPlessForms();
        }, 100);
    });
    
    // Initialize forms when AJAX content loads
    $(document).on('gform_ajax_iframe_content_loaded', function(event, formId) {
        setTimeout(function() {
            initializeOTPlessForms();
        }, 100);
    });
    
    // Also check for forms periodically to catch any dynamically loaded forms
    setInterval(function() {
        const uninitializedForms = $('.gform_wrapper form:not(.otpless-initialized)');
        if (uninitializedForms.length > 0) {
            initializeOTPlessForms();
        }
    }, 2000); // Check every 2 seconds
    
    // Show OTPless authentication modal
    function showOTPlessModal() {
        // Check if modal exists
        if ($('#otpless-modal').length === 0) {
            createOTPlessModal();
        }
        
                 $('#otpless-modal').show();
         $('#otpless-status').html('');
         $('#otpless-contact').val('');
         $('#otpless-otp').val('');
        $('#otpless-country-code').hide();
        
        // Reset modal state
        $('.otpless-auth-options').show();
        $('#otp-verification').hide();
    }
    
    // Create OTPless modal if it doesn't exist
    function createOTPlessModal() {
        const modalHTML = `
            <div id="otpless-modal" class="otpless-modal" style="display: none;">
                <div class="otpless-modal-content">
                    <div class="otpless-modal-header">
                         <h2>Meet Magento India</h2>
                         <p>Let's Sign In</p>
                         <span class="otpless-close">&times;</span>
                     </div>
                    
                    <div class="otpless-modal-body">
                        <div class="otpless-auth-options">
                            <div class="otpless-option">
                                <div class="contact-input-group">
                                    <select id="otpless-country-code" class="country-code-select" style="display:none;">
                                        <option value="+1">United States - +1</option>
                                        <option value="+44">United Kingdom - +44</option>
                                        <option value="+61">Australia - +61</option>
                                        <option value="+81">Japan - +81</option>
                                        <option value="+82">South Korea - +82</option>
                                        <option value="+86">China - +86</option>
                                        <option value="+91" selected>India - +91</option>
                                        <option value="+971">UAE - +971</option>
                                        <option value="+973">Bahrain - +973</option>
                                        <option value="+974">Qatar - +974</option>
                                        <option value="+966">Saudi Arabia - +966</option>
                                        <option value="+880">Bangladesh - +880</option>
                                        <option value="+92">Pakistan - +92</option>
                                        <option value="+94">Sri Lanka - +94</option>
                                        <option value="+93">Afghanistan - +93</option>
                                        <option value="+355">Albania - +355</option>
                                        <option value="+213">Algeria - +213</option>
                                        <option value="+376">Andorra - +376</option>
                                        <option value="+244">Angola - +244</option>
                                        <option value="+1268">Antigua and Barbuda - +1268</option>
                                        <option value="+54">Argentina - +54</option>
                                        <option value="+374">Armenia - +374</option>
                                        <option value="+43">Austria - +43</option>
                                        <option value="+994">Azerbaijan - +994</option>
                                        <option value="+1242">Bahamas - +1242</option>
                                        <option value="+375">Belarus - +375</option>
                                        <option value="+32">Belgium - +32</option>
                                        <option value="+501">Belize - +501</option>
                                        <option value="+229">Benin - +229</option>
                                        <option value="+975">Bhutan - +975</option>
                                        <option value="+591">Bolivia - +591</option>
                                        <option value="+387">Bosnia and Herzegovina - +387</option>
                                        <option value="+267">Botswana - +267</option>
                                        <option value="+55">Brazil - +55</option>
                                        <option value="+673">Brunei - +673</option>
                                        <option value="+359">Bulgaria - +359</option>
                                        <option value="+226">Burkina Faso - +226</option>
                                        <option value="+257">Burundi - +257</option>
                                        <option value="+855">Cambodia - +855</option>
                                        <option value="+237">Cameroon - +237</option>
                                        <option value="+238">Cape Verde - +238</option>
                                        <option value="+236">Central African Republic - +236</option>
                                        <option value="+235">Chad - +235</option>
                                        <option value="+56">Chile - +56</option>
                                        <option value="+57">Colombia - +57</option>
                                        <option value="+269">Comoros - +269</option>
                                        <option value="+242">Congo - +242</option>
                                        <option value="+506">Costa Rica - +506</option>
                                        <option value="+385">Croatia - +385</option>
                                        <option value="+53">Cuba - +53</option>
                                        <option value="+357">Cyprus - +357</option>
                                        <option value="+420">Czech Republic - +420</option>
                                        <option value="+45">Denmark - +45</option>
                                        <option value="+253">Djibouti - +253</option>
                                        <option value="+1809">Dominican Republic - +1809</option>
                                        <option value="+593">Ecuador - +593</option>
                                        <option value="+20">Egypt - +20</option>
                                        <option value="+503">El Salvador - +503</option>
                                        <option value="+240">Equatorial Guinea - +240</option>
                                        <option value="+291">Eritrea - +291</option>
                                        <option value="+372">Estonia - +372</option>
                                        <option value="+251">Ethiopia - +251</option>
                                        <option value="+679">Fiji - +679</option>
                                        <option value="+358">Finland - +358</option>
                                        <option value="+33">France - +33</option>
                                        <option value="+241">Gabon - +241</option>
                                        <option value="+220">Gambia - +220</option>
                                        <option value="+995">Georgia - +995</option>
                                        <option value="+49">Germany - +49</option>
                                        <option value="+233">Ghana - +233</option>
                                        <option value="+30">Greece - +30</option>
                                        <option value="+502">Guatemala - +502</option>
                                        <option value="+224">Guinea - +224</option>
                                        <option value="+245">Guinea-Bissau - +245</option>
                                        <option value="+592">Guyana - +592</option>
                                        <option value="+509">Haiti - +509</option>
                                        <option value="+504">Honduras - +504</option>
                                        <option value="+852">Hong Kong - +852</option>
                                        <option value="+36">Hungary - +36</option>
                                        <option value="+354">Iceland - +354</option>
                                        <option value="+62">Indonesia - +62</option>
                                        <option value="+98">Iran - +98</option>
                                        <option value="+964">Iraq - +964</option>
                                        <option value="+353">Ireland - +353</option>
                                        <option value="+972">Israel - +972</option>
                                        <option value="+39">Italy - +39</option>
                                        <option value="+225">Ivory Coast - +225</option>
                                        <option value="+1876">Jamaica - +1876</option>
                                        <option value="+962">Jordan - +962</option>
                                        <option value="+7">Kazakhstan - +7</option>
                                        <option value="+254">Kenya - +254</option>
                                        <option value="+996">Kyrgyzstan - +996</option>
                                        <option value="+856">Laos - +856</option>
                                        <option value="+371">Latvia - +371</option>
                                        <option value="+961">Lebanon - +961</option>
                                        <option value="+231">Liberia - +231</option>
                                        <option value="+218">Libya - +218</option>
                                        <option value="+370">Lithuania - +370</option>
                                        <option value="+352">Luxembourg - +352</option>
                                        <option value="+853">Macau - +853</option>
                                        <option value="+389">Macedonia - +389</option>
                                        <option value="+261">Madagascar - +261</option>
                                        <option value="+265">Malawi - +265</option>
                                        <option value="+60">Malaysia - +60</option>
                                        <option value="+960">Maldives - +960</option>
                                        <option value="+223">Mali - +223</option>
                                        <option value="+356">Malta - +356</option>
                                        <option value="+222">Mauritania - +222</option>
                                        <option value="+230">Mauritius - +230</option>
                                        <option value="+52">Mexico - +52</option>
                                        <option value="+373">Moldova - +373</option>
                                        <option value="+377">Monaco - +377</option>
                                        <option value="+976">Mongolia - +976</option>
                                        <option value="+212">Morocco - +212</option>
                                        <option value="+258">Mozambique - +258</option>
                                        <option value="+95">Myanmar - +95</option>
                                        <option value="+264">Namibia - +264</option>
                                        <option value="+977">Nepal - +977</option>
                                        <option value="+31">Netherlands - +31</option>
                                        <option value="+64">New Zealand - +64</option>
                                        <option value="+505">Nicaragua - +505</option>
                                        <option value="+227">Niger - +227</option>
                                        <option value="+234">Nigeria - +234</option>
                                        <option value="+47">Norway - +47</option>
                                        <option value="+968">Oman - +968</option>
                                        <option value="+92">Pakistan - +92</option>
                                        <option value="+507">Panama - +507</option>
                                        <option value="+675">Papua New Guinea - +675</option>
                                        <option value="+595">Paraguay - +595</option>
                                        <option value="+51">Peru - +51</option>
                                        <option value="+63">Philippines - +63</option>
                                        <option value="+48">Poland - +48</option>
                                        <option value="+351">Portugal - +351</option>
                                        <option value="+974">Qatar - +974</option>
                                        <option value="+40">Romania - +40</option>
                                        <option value="+7">Russia - +7</option>
                                        <option value="+250">Rwanda - +250</option>
                                        <option value="+966">Saudi Arabia - +966</option>
                                        <option value="+221">Senegal - +221</option>
                                        <option value="+381">Serbia - +381</option>
                                        <option value="+65">Singapore - +65</option>
                                        <option value="+421">Slovakia - +421</option>
                                        <option value="+386">Slovenia - +386</option>
                                        <option value="+27">South Africa - +27</option>
                                        <option value="+34">Spain - +34</option>
                                        <option value="+94">Sri Lanka - +94</option>
                                        <option value="+249">Sudan - +249</option>
                                        <option value="+46">Sweden - +46</option>
                                        <option value="+41">Switzerland - +41</option>
                                        <option value="+963">Syria - +963</option>
                                        <option value="+886">Taiwan - +886</option>
                                        <option value="+992">Tajikistan - +992</option>
                                        <option value="+255">Tanzania - +255</option>
                                        <option value="+66">Thailand - +66</option>
                                        <option value="+228">Togo - +228</option>
                                        <option value="+216">Tunisia - +216</option>
                                        <option value="+90">Turkey - +90</option>
                                        <option value="+993">Turkmenistan - +993</option>
                                        <option value="+256">Uganda - +256</option>
                                        <option value="+380">Ukraine - +380</option>
                                        <option value="+971">United Arab Emirates - +971</option>
                                        <option value="+44">United Kingdom - +44</option>
                                        <option value="+1">United States - +1</option>
                                        <option value="+598">Uruguay - +598</option>
                                        <option value="+998">Uzbekistan - +998</option>
                                        <option value="+58">Venezuela - +58</option>
                                        <option value="+84">Vietnam - +84</option>
                                        <option value="+967">Yemen - +967</option>
                                        <option value="+260">Zambia - +260</option>
                                        <option value="+263">Zimbabwe - +263</option>
                                    </select>
                                    <input type="text" id="otpless-contact" placeholder="Enter Phone or Email" class="unified-input">
                                </div>
                            </div>
                           
                            <button id="otpless-send-otp" class="otpless-btn otpless-btn-primary">
                                Continue
                            </button>
                       </div>
                        
                        <div id="otp-verification" class="otp-verification" style="display: none;">
                            <div class="otp-header">
                                <div class="otp-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" fill="#4CAF50"/>
                                    </svg>
                                </div>
                                <h3>Enter Verification Code</h3>
                                <p>We've sent a 6-digit code to your device</p>
                            </div>
                            
                            <div class="otpless-option">
                                <label for="otpless-otp">Verification Code</label>
                                <div class="otp-input-container">
                                    <input type="text" id="otpless-otp" placeholder="000000" maxlength="6" class="otp-input">
                                    <div class="otp-input-focus"></div>
                                </div>
                            </div>
                            
                            <div class="otp-actions">
                                <button id="otpless-verify-otp" class="otpless-btn otpless-btn-success">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
                                    </svg>
                                    Verify & Continue
                                </button>
                                <button id="otpless-resend-otp" class="otpless-btn otpless-btn-secondary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z" fill="currentColor"/>
                                    </svg>
                                    Resend Code
                                </button>
                            </div>
                        </div>
                        
                                                 <div id="otpless-status" class="otpless-status"></div>
                         

                    </div>
                </div>
            </div>
        `;
        
                 $('body').append(modalHTML);
         
         // Bind event handlers after modal is created
         bindModalEventHandlers();
     }
     
         // Bind event handlers for modal
    function bindModalEventHandlers() {
        // Handle Google Sign-In
        $(document).off('click', '#otpless-google-signin').on('click', '#otpless-google-signin', function() {
            const $button = $(this);
            const originalText = $button.html();
            $button.html('Connecting...').prop('disabled', true);
            
            // Redirect to OTPless Google auth
            initiateGoogleSignIn();
        });
        
        // Auto-detect and toggle country code for phone vs email input
        $(document).off('input', '#otpless-contact').on('input', '#otpless-contact', function() {
            const raw = $(this).val();
            const cleaned = raw.replace(/[\s\-\(\)]/g, '');
            const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned);
            const isPhoneLike = /^\+?[0-9]{7,}$/.test(cleaned) || /^[0-9]{10,}$/.test(cleaned);

            console.log('Input detection:', { raw, cleaned, isEmail, isPhoneLike });

            const $code = $('#otpless-country-code');
            // Show/hide country code based on input type
            if (isEmail) {
                $code.hide();
                console.log('Email detected, hiding country code');
            } else if (isPhoneLike || /\d{5,}/.test(cleaned)) {
                $code.show();
                console.log('Phone detected, showing country code');
                
                // Handle country code extraction if user types +country code
                if (cleaned.startsWith('+')) {
                    const ccMatch = cleaned.match(/^(\+\d{1,3})(\d*)$/);
                    if (ccMatch) {
                        const cc = ccMatch[1];
                        const rest = ccMatch[2];
                        if ($code.find(`option[value="${cc}"]`).length) {
                            $code.val(cc);
                            $(this).val(rest);
                            console.log('Country code extracted:', cc, 'rest:', rest);
                        }
                    }
                }
            } else {
                $code.hide();
                console.log('Neither email nor phone, hiding country code');
            }
        });
        
         // Handle OTP sending
        $(document).off('click', '#otpless-send-otp').on('click', '#otpless-send-otp', function() {
 			 const contact = $('#otpless-contact').val().trim();
			 const $code = $('#otpless-country-code');
 			 
 			 if (!contact) {
 				 $('#otpless-status').html('<div class="error">Please enter your phone number or email address</div>');
 				 return;
 			 }
 			 
 			 // Auto-detect if input is email or phone
 			 const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contact);
			 const isPhone = /^[\+]?[0-9]{7,}$/.test(contact.replace(/[\s\-\(\)]/g, '')) || /^[0-9]{10,}$/.test(contact.replace(/[\s\-\(\)]/g, ''));
 			 
             if (!isEmail && !isPhone) {
                 $('#otpless-status').html('<div class="error">Please enter a valid phone number or email address</div>');
                 return;
             }
 			 
 			 // Auto-determine channel based on input type
 			 const channel = isEmail ? 'EMAIL' : 'WHATSAPP';
 			 const email = isEmail ? contact : '';
			 let phone = '';
			 if (!isEmail) {
				 const digits = contact.replace(/[^0-9]/g, '');
				 let cc = '+91';
				 if ($code.is(':visible')) {
					 cc = $code.val();
				 } else if (contact.startsWith('+')) {
					 phone = contact;
				 }
				 if (!phone) {
					 phone = cc + digits;
				 }
			 }
 			 
 			 const $button = $(this);
 			 const originalText = $button.text();
 			 $button.text('Sending...').prop('disabled', true);
 			 
 			 // Choose action based on channel
 			 let action = 'otpless_send_otp';
 			 let requestData = {
 				 nonce: otpless_ajax.nonce,
 				 email: email,
 				 phone: phone,
 				 channel: channel
 			 };
 			 
 			 if (channel === 'EMAIL') {
 				 action = 'send_email_otp';
 				 requestData = {
 					 action: action,
 					 nonce: otpless_ajax.email_otp_nonce,
 					 email: email
 				 };
 			 } else {
 				 requestData.action = action;
 			 }
 			 
 			 $.ajax({
 			   url: otpless_ajax.ajax_url,
 			   type: 'POST',
 			   data: requestData,
 			   success: function(response) {
 			       const data = JSON.parse(response);
 			      if (data.success) {
 			          $('#otpless-status').html('<div class="success">' + data.message + '</div>');
 			          
 			          // Store OTP key for email verification
 			          if (channel === 'EMAIL' && data.otp_key) {
 			              $('#otp-verification').data('otp-key', data.otp_key);
 			          }
 			          
 			          // Show OTP verification section
 			          $('.otpless-auth-options').hide();
 			          $('#otp-verification').show();
 			          
 			          // Focus on OTP input
 			          $('#otpless-otp').focus();
 			      } else {
 			          $('#otpless-status').html('<div class="error">' + data.message + '</div>');
 			      }
 			  },
 			  error: function(xhr, status, error) {
 			      $('#otpless-status').html('<div class="error">Failed to send OTP. Please try again.</div>');
 			  },
 			  complete: function() {
 			      $button.text(originalText).prop('disabled', false);
 			  }
 		 });
 		});
         
         // Handle OTP verification
         $(document).off('click', '#otpless-verify-otp').on('click', '#otpless-verify-otp', function() {
             const otp = $('#otpless-otp').val().trim();
             const contact = $('#otpless-contact').val().trim();
             
             // Auto-detect channel based on contact type
             const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contact);
             const channel = isEmail ? 'EMAIL' : 'WHATSAPP';
             
             if (!otp) {
                 $('#otpless-status').html('<div class="error">Please enter the OTP</div>');
                 return;
             }
             
             const $button = $(this);
             const originalText = $button.text();
             $button.text('Verifying...').prop('disabled', true);
             
             // Choose action based on channel
             let action = 'otpless_verify_otp';
             let requestData = {
                 nonce: otpless_ajax.nonce,
                 otp: otp
             };
             
             if (channel === 'EMAIL') {
                 action = 'verify_email_otp';
                 const otpKey = $('#otp-verification').data('otp-key');
                 requestData = {
                     action: action,
                     nonce: otpless_ajax.email_otp_nonce,
                     otp: otp,
                     otp_key: otpKey
                 };
             } else {
                 requestData.action = action;
             }
             
             $.ajax({
                 url: otpless_ajax.ajax_url,
                 type: 'POST',
                 data: requestData,
                 success: function(response) {
                     const data = JSON.parse(response);
                     
                     if (data.success) {
                         setUserAuthenticated(data.user);
                         handleSuccessfulAuthentication();
                     } else {
                         $('#otpless-status').html('<div class="error">' + data.message + '</div>');
                     }
                 },
                 error: function(xhr, status, error) {
                     $('#otpless-status').html('<div class="error">Failed to verify OTP. Please try again.</div>');
                 },
                 complete: function() {
                     $button.text(originalText).prop('disabled', false);
                 }
             });
         });
         
         // Handle OTP resend
         $(document).off('click', '#otpless-resend-otp').on('click', '#otpless-resend-otp', function() {
             const $button = $(this);
             const originalText = $button.text();
             $button.text('Sending...').prop('disabled', true);
             
             // Trigger send OTP again
             $('#otpless-send-otp').trigger('click');
             
             setTimeout(function() {
                 $button.text(originalText).prop('disabled', false);
             }, 2000);
         });
         
         // Handle OTP input - auto-submit when 6 digits entered
         $(document).off('input', '#otpless-otp').on('input', '#otpless-otp', function() {
             const otp = $(this).val().trim();
             if (otp.length === 6) {
                 $('#otpless-verify-otp').trigger('click');
             }
         });
         
         // Handle modal close
         $(document).off('click', '.otpless-close').on('click', '.otpless-close', function() {
             hideOTPlessModal();
         });
         
                   // Close modal when clicking outside
          $(document).off('click', '#otpless-modal').on('click', '#otpless-modal', function(e) {
              if (e.target.id === 'otpless-modal') {
                  hideOTPlessModal();
              }
          });
          

     }
     
     // Hide OTPless authentication modal
     function hideOTPlessModal() {
         $('#otpless-modal').hide();
     }
    
    // Check if user is authenticated
    function isUserAuthenticated() {
        return sessionStorage.getItem('otpless_authenticated') === 'true';
    }
    
    // Set user as authenticated
    function setUserAuthenticated(userData) {
        sessionStorage.setItem('otpless_authenticated', 'true');
        sessionStorage.setItem('otpless_user_data', JSON.stringify(userData));
    }
    
    // Clear authentication
    function clearAuthentication() {
        sessionStorage.removeItem('otpless_authenticated');
        sessionStorage.removeItem('otpless_user_data');
    }
    
              // Handle successful authentication
     function handleSuccessfulAuthentication() {
         const userData = JSON.parse(sessionStorage.getItem('otpless_user_data') || '{}');
         
         $('#otpless-status').html('<div class="success">Authentication successful! Submitting form...</div>');
         
         setTimeout(function() {
             hideOTPlessModal();
             
             // Submit the pending form
             if (currentForm && pendingFormData) {
                 // Add user data to form fields if they exist
                 if (userData.email) {
                     currentForm.find('input[type="email"], input[name*="email"]').val(userData.email);
                 }
                 if (userData.phone_number) {
                     currentForm.find('input[type="tel"], input[name*="phone"]').val(userData.phone_number);
                 }
                 if (userData.name) {
                     currentForm.find('input[name*="name"]').val(userData.name);
                 }
                 
                 // Submit the form
                 currentForm.submit();
                 
                 // Clear pending data
                 currentForm = null;
                 pendingFormData = null;
             }
         }, 1000);
     }
    
    // Handle OTPless callback (when user clicks magic link)
    function handleOTPlessCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        const code = urlParams.get('code');
        
        if (token || code) {
            $.ajax({
                url: otpless_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'otpless_verify_auth',
                    nonce: otpless_ajax.nonce,
                    token: token,
                    code: code
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        setUserAuthenticated(data.user);
                        
                        // Show success message
                        $('body').append('<div id="otpless-success" style="position: fixed; top: 20px; right: 20px; background: #4CAF50; color: white; padding: 15px; border-radius: 5px; z-index: 9999;">Authentication successful! You can now submit the form.</div>');
                        
                        // Remove success message after 5 seconds
                        setTimeout(function() {
                            $('#otpless-success').remove();
                        }, 5000);
                        
                        // Clean up URL
                        window.history.replaceState({}, document.title, window.location.pathname);
                    } else {
                        alert('Authentication failed: ' + data.message);
                    }
                },
                error: function() {
                    alert('Authentication verification failed. Please try again.');
                }
            });
        }
    }
    
    // Check for OTPless callback on page load
    handleOTPlessCallback();
    
    // Add logout functionality (optional)
    function logout() {
        clearAuthentication();
        location.reload();
    }
    
    // Add logout button to forms (optional)
    $('.gform_wrapper').each(function() {
        const $form = $(this);
        if (isUserAuthenticated()) {
            const userData = JSON.parse(sessionStorage.getItem('otpless_user_data') || '{}');
            const $logoutButton = $('<button type="button" class="otpless-logout" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 3px; margin-left: 10px; cursor: pointer;">Logout</button>');
            
            $logoutButton.on('click', function() {
                if (confirm('Are you sure you want to logout?')) {
                    logout();
                }
            });
            
            $form.find('.gform_footer').append($logoutButton);
            
            // Show authenticated user info
            const userInfo = $('<div class="otpless-user-info" style="background: #e8f5e8; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 14px;">Authenticated as: ' + 
                (userData.name || userData.email || userData.phone_number) + '</div>');
            $form.prepend(userInfo);
        }
    });
    
    // Handle form validation
    $(document).on('gform_post_render', function(event, formId, current_page) {
        // Add custom validation for OTPless authentication
        $('.gform_wrapper form').each(function() {
            const $form = $(this);
            
            $form.on('submit', function(e) {
                // If form requires authentication and user is not authenticated, prevent submission
                if ($form.hasClass('otpless-required') && !isUserAuthenticated()) {
                    e.preventDefault();
                    showOTPlessModal();
                    return false;
                }
            });
        });
    });
    
    // Initiate Google Sign-In flow
    function initiateGoogleSignIn() {
        // Build OTPless OAuth URL for Google
        const baseUrl = 'https://oidc.otpless.app/auth/v1/authorize';
        const params = new URLSearchParams({
            client_id: otpless_ajax.client_id,
            redirect_uri: otpless_ajax.redirect_uri,
            response_type: 'code',
            scope: 'openid email profile',
            provider: 'google'
        });
        
        const authUrl = `${baseUrl}?${params.toString()}`;
        
        // Store form data before redirect
        if (currentForm && pendingFormData) {
            sessionStorage.setItem('otpless_pending_form_data', pendingFormData);
            sessionStorage.setItem('otpless_pending_form_id', currentForm.attr('id') || 'gform_1');
        }
        
        // Redirect to Google Sign-In
        window.location.href = authUrl;
    }
    
    // Handle Google Sign-In callback on page load
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        const error = urlParams.get('error');
        
        if (error) {
            $('#otpless-status').html('<div class="error">Authentication failed. Please try again.</div>');
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
            return;
        }
        
        if (code) {
            // Exchange code for user details
            $.ajax({
                url: otpless_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'otpless_verify_code',
                    nonce: otpless_ajax.nonce,
                    code: code
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Mark user as authenticated
                        setUserAuthenticated(data.user);
                        
                        // Submit the pending form
                        const pendingFormData = sessionStorage.getItem('otpless_pending_form_data');
                        const pendingFormId = sessionStorage.getItem('otpless_pending_form_id');
                        
                        if (pendingFormData && pendingFormId) {
                            const $form = $('#' + pendingFormId);
                            if ($form.length) {
                                // Clean up URL and submit form
                                window.history.replaceState({}, document.title, window.location.pathname);
                                sessionStorage.removeItem('otpless_pending_form_data');
                                sessionStorage.removeItem('otpless_pending_form_id');
                                
                                // Submit form
                                $form.off('submit');
                                $form.submit();
                            }
                        } else {
                            // Clean up URL
                            window.history.replaceState({}, document.title, window.location.pathname);
                        }
                    } else {
                        $('#otpless-status').html('<div class="error">' + (data.message || 'Authentication failed') + '</div>');
                        // Clean up URL
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                },
                error: function() {
                    $('#otpless-status').html('<div class="error">Authentication failed. Please try again.</div>');
                    // Clean up URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });
        }
    });
    
    // Mark success to clear auth on next load (for non-AJAX submissions)
	$(document).on('gform_confirmation_loaded', function() {
		clearAuthentication();
	});

	// For non-AJAX redirects: if flag exists, clear and remove it
	if (sessionStorage.getItem('otpless_clear_after_submit') === 'true') {
		clearAuthentication();
		sessionStorage.removeItem('otpless_clear_after_submit');
	}

	// When we submit the form after auth, set a flag to clear on next page
	$(document).on('submit', '.gform_wrapper form', function() {
		if (isUserAuthenticated()) {
			sessionStorage.setItem('otpless_clear_after_submit', 'true');
		}
	});
    
    // CSS is now handled by the external CSS file
    
});
