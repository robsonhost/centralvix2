<?php
/* Smarty version 4.5.3, created on 2026-10-19 13:44:52
  from '/home/centralvixcom/public_html/admin/templates/licenseerror.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6ad6490417c0c4_92120425',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a9707531b9789f948315f96b1aee8d560ff61876' => 
    array (
      0 => '/home/centralvixcom/public_html/admin/templates/licenseerror.tpl',
      1 => 1790173046,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ad6490417c0c4_92120425 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php if ($_smarty_tpl->tpl_vars['licenseError']->value == "suspended") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'License Suspended');?>
        <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "pending") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'License Key Pending');?>
        <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "invalid") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'Invalid License');?>
        <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "expired") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'Expired License');?>
        <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "version") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'Renewal Required');?>
        <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "noconnection") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'Connection Error');?>
        <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "error") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'Licensing Error');?>
        <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "change") {?>
            <?php $_smarty_tpl->_assignInScope('pageTitle', 'Update License Key');?>
        <?php }?>
        <title>WHMCS - <?php echo $_smarty_tpl->tpl_vars['pageTitle']->value;?>
</title>

        <link href="templates/login.min.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <?php echo '<script'; ?>
 src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"><?php echo '</script'; ?>
>
          <?php echo '<script'; ?>
 src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"><?php echo '</script'; ?>
>
        <![endif]-->
      </head>
      <body>
        <div class="login-container">
            <h1 class="logo">
                <a href="login.php">
                    <img src="<?php echo $_smarty_tpl->tpl_vars['BASE_PATH_IMG']->value;?>
/whmcs.png" alt="WHMCS" />
                </a>
            </h1>
            <div class="login-body"<?php if ($_smarty_tpl->tpl_vars['licenseError']->value == "noconnection" || $_smarty_tpl->tpl_vars['licenseError']->value == "change") {?> style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;"<?php }?>>
                <h2><?php echo $_smarty_tpl->tpl_vars['pageTitle']->value;?>
</h2>
                <?php if ($_smarty_tpl->tpl_vars['infoMsg']->value) {?>
                    <div class="alert alert-info text-center" role="alert">
                        <?php echo $_smarty_tpl->tpl_vars['infoMsg']->value;?>

                    </div>
                <?php }?>
                <?php if ($_smarty_tpl->tpl_vars['successMsg']->value) {?>
                    <div class="alert alert-success text-center" role="alert">
                        <?php echo $_smarty_tpl->tpl_vars['successMsg']->value;?>

                    </div>
                <?php }?>
                <?php if ($_smarty_tpl->tpl_vars['errorMsg']->value) {?>
                    <div class="alert alert-danger text-center" role="alert">
                        <?php echo $_smarty_tpl->tpl_vars['errorMsg']->value;?>

                    </div>
                <?php }?>

                <?php if ($_smarty_tpl->tpl_vars['licenseError']->value == "suspended") {?>
                    <p>Your license key has been suspended.  Possible reasons for this include:</p>
                    <ul>
                        <li>Your license is overdue on payment</li>
                        <li>Your license has been suspended for being used on a banned
                            domain</li>
                        <li>Your license was found to be being used against the End User
                            License Agreement</li>
                    </ul>
                <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "pending") {?>
                    <p>The WHMCS License Key you just tried to access is still pending. This error occurs when we have not yet received the payment for your license.</p>
                <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "invalid") {?>
                    <p>Your license key is invalid. Possible reasons for this include:</p>
                    <ul>
                        <li>The license key has been entered incorrectly</li>
                        <li>The domain being used to access your install has changed</li>
                        <li>The IP address your install is located on has changed</li>
                        <li>The directory you are using has changed</li>
                    </ul>
                    <p>
                        If required, you can reissue your license on-demand from our client
                        area @ <a href="https://www.whmcs.com/members/clientarea.php"
                                  target="_blank">www.whmcs.com/members/clientarea.php</a> which will
                        update the allowed location details.
                    </p>
                <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "expired") {?>
                    <p>Your license key has expired!  To resolve this you can:</p>
                    <ul>
                        <li>Check your email for a copy of the invoice or payment reminders</li>
                        <li>Order a new license from <a href="https://www.whmcs.com/order"
                                                        target="_blank">www.whmcs.com/order</a></li>
                    </ul>
                    <p>
                        If you feel this message to be an error, please contact us @ <a
                                href="https://www.whmcs.com/support" target="_blank">www.whmcs.com/support</a>
                    </p>
                <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "version") {?>
                    <p>
                        You are using an Owned License for which the support & updates
                        validity period expired before this release.<br/><br/>
                        To use this version of WHMCS, you must purchase a new license key.<br/><br/>
                        Learn more and obtain a new license at <a
                            href="https://www.whmcs.com/owned-license-upgrade?utm_source=version&utm_medium=inproduct&utm_campaign=ownedeol">www.whmcs.com/owned-license-upgrade</a>.
                    </p>
                    <p>
                        If you feel this message to be an error, please contact us @ <a
                                href="https://www.whmcs.com/support" target="_blank">www.whmcs.com/support</a>
                    </p>
                <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "noconnection") {?>
                    <p>WHMCS has not been able to verify your license for the last few days.</p>
                    <p>Before you can access your WHMCS Admin Area again, the license
                        needs to be validated successfully. Please check & ensure that you
                        don't have a firewall or DNS rule blocking outgoing connections to
                        our website.</p>
                    <p>
                        For help, see our <a
                                href="https://go.whmcs.com/1949/license-troubleshooting"
                                target="_blank">documentation</a>.
                    </p>
                <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "error") {?>
                    <p>Unable to perform license validation due to the following <strong>local server</strong> configuration issue:</p>
                    <div class="alert alert-danger">
                        <?php echo $_smarty_tpl->tpl_vars['licenseCheckError']->value;?>

                    </div>
                    <p>Please resolve the error shown above to enable license validation to complete successfully.</p>
                    <p>
                        For help, see our <a
                                href="https://go.whmcs.com/1949/license-troubleshooting"
                                target="_blank">documentation</a>.
                    </p>
                <?php } elseif ($_smarty_tpl->tpl_vars['licenseError']->value == "change") {?>
                    <p>You can change your license key by entering your admin login details
                        and new key below. Requires full admin access permissions.</p>
                    <form method="post"
                          action="?licenseerror=change&updatekey=true">
                        <div class="form-group">
                            <input type="text" class="form-control" name="username" placeholder="Username" autofocus>
                        </div>
                        <div class="form-group">
                            <input type="password" class="form-control" name="password" placeholder="Password">
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="license_key" placeholder="New License Key">
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <input type="submit" value="Change License Key" class="btn btn-primary btn-block">
                            </div>
                        </div>
                    </form>
                <?php }?>
            </div>
            <div class="footer">
                <?php if ($_smarty_tpl->tpl_vars['licenseError']->value != "noconnection" && $_smarty_tpl->tpl_vars['licenseError']->value != "error" && $_smarty_tpl->tpl_vars['licenseError']->value != "change") {?>
                    <a href="licenseerror.php?licenseerror=change">Click here to enter a new license key.</a>
                <?php }?>
            </div>
        </div>
        <div class="poweredby text-center">
            <a href="https://www.whmcs.com/" target="_blank">Powered by WHMCS</a>
        </div>
        <?php echo '<script'; ?>
 type="text/javascript" src="templates/login.min.js"><?php echo '</script'; ?>
>
    </body>
</html>
<?php }
}
