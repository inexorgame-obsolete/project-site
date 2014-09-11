
<?php $cap = create_captcha($captcha); ?>
<div class="centered">
      <h1 class="in-eyecatcher text-contrast">Register</h1>
      <p>Please enter your information below.</p>

      <div id="infoMessage"><?php echo $message;?></div>

      <?php echo form_open("user/register");?>

            <p>
                  <?php echo lang('create_user_fname_label', 'first_name');?> <br />
                  <?php echo form_input($first_name);?>
            </p>

            <p>
                  <?php echo lang('create_user_lname_label', 'last_name');?> <br />
                  <?php echo form_input($last_name);?>
            </p>

            <p>
                  <?php echo lang('create_user_username_label', 'username');?>* <br />
                  <?php echo form_input($username);?>
            </p>

            <p>
                  <?php echo lang('create_user_email_label', 'email');?>* <br />
                  <?php echo form_input($email);?>
            </p>

            <p>
                  <?php echo lang('create_user_password_label', 'password');?>* <br />
                  <?php echo form_input($password);?>
            </p>

            <p>
                  <?php echo lang('create_user_password_confirm_label', 'password_confirm');?>* <br />
                  <?php echo form_input($password_confirm);?>
            </p>

            <p>
                  <?php echo $cap['image']; ?><br />
                  <?php echo form_input($solve_captcha);?>
            </p>

            <p><?php echo form_submit('submit', 'Register');?></p>

      <?php echo form_close();?>
</div>