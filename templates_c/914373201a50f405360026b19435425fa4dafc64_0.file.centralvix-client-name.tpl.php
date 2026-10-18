<?php
/* Smarty version 4.5.3, created on 2026-09-05 23:15:15
  from '/home/centralvixcom/public_html/templates/centralvix-client/includes/centralvix-client-name.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6a9cccb32adad8_76459897',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '914373201a50f405360026b19435425fa4dafc64' => 
    array (
      0 => '/home/centralvixcom/public_html/templates/centralvix-client/includes/centralvix-client-name.tpl',
      1 => 1786328738,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a9cccb32adad8_76459897 (Smarty_Internal_Template $_smarty_tpl) {
if (!empty($_smarty_tpl->tpl_vars['client']->value['companyname'])) {?>
    <?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['client']->value['companyname'], ENT_QUOTES, 'UTF-8', true);?>

<?php } elseif (!empty($_smarty_tpl->tpl_vars['client']->value['fullName'])) {?>
    <?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['client']->value['fullName'], ENT_QUOTES, 'UTF-8', true);?>

<?php } elseif (!empty($_smarty_tpl->tpl_vars['clientsdetails']->value['companyname'])) {?>
    <?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['clientsdetails']->value['companyname'], ENT_QUOTES, 'UTF-8', true);?>

<?php } elseif (!empty($_smarty_tpl->tpl_vars['clientsdetails']->value['firstname'])) {?>
    <?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['clientsdetails']->value['firstname'], ENT_QUOTES, 'UTF-8', true);?>
 <?php echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['clientsdetails']->value['lastname'], ENT_QUOTES, 'UTF-8', true);?>

<?php } else { ?>
    <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'clientarea'),$_smarty_tpl ) );?>

<?php }
}
}
