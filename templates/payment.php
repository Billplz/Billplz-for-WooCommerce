<?php

defined('ABSPATH') || exit;

?>
<p class="form-row validate-required">
  <label><?php echo esc_html_e('Choose Payment Method', 'bfw'); ?> 
    <span class="required">*</span>
  </label>
  
  <select name="billplz_bank">
      <option value="" disabled selected></option>
  <?php foreach ($bank_name as $key => $value) {
    if (empty($gateways)) {
      break;
    }
    foreach ($gateways['payment_gateways'] as $gateway) {
      if ($gateway['code'] === $key && $gateway['active'] && in_array($gateway['category'], $collection_gateways)) {
        ?>
      <option value="<?php echo $gateway['code']; ?>"><?php echo $bank_name[$gateway['code']] ? strtoupper($bank_name[$gateway['code']]) : $gateway['code']; ?></option>
      <?php
      }
    }
  }?>
  </select>
</p>