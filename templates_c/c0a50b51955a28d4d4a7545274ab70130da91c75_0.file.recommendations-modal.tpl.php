<?php
/* Smarty version 4.5.3, created on 2026-08-29 23:15:08
  from '/home/centralvixcom/public_html/templates/orderforms/standard_cart/recommendations-modal.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6a9367fc197184_68453428',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c0a50b51955a28d4d4a7545274ab70130da91c75' => 
    array (
      0 => '/home/centralvixcom/public_html/templates/orderforms/standard_cart/recommendations-modal.tpl',
      1 => 1783419668,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:orderforms/standard_cart/includes/product-recommendations.tpl' => 1,
  ),
),false)) {
function content_6a9367fc197184_68453428 (Smarty_Internal_Template $_smarty_tpl) {
if (in_array($_smarty_tpl->tpl_vars['templatefile']->value,array('configureproductdomain','configureproduct'))) {?>
<div class="hidden" id="divProductHasRecommendations" data-value="<?php echo $_smarty_tpl->tpl_vars['productinfo']->value['hasRecommendations'];?>
"></div>
<?php }?>
<div class="modal fade" id="recommendationsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="float-left pull-left">
                    <?php if (in_array($_smarty_tpl->tpl_vars['templatefile']->value,array('viewcart','complete','checkout'))) {?>
                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"recommendations.title.generic"),$_smarty_tpl ) );?>

                    <?php } else { ?>
                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"recommendations.title.addedTo"),$_smarty_tpl ) );?>

                    <?php }?>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <div class="clearfix"></div>
            </div>
            <div class="modal-body">
                <?php $_smarty_tpl->_subTemplateRender("file:orderforms/standard_cart/includes/product-recommendations.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
            </div>
            <div class="modal-footer">
                <a class="btn btn-primary" href="#" id="btnContinueRecommendationsModal" data-dismiss="modal" role="button">
                    <span class="w-hidden hidden"><i class="fas fa-spinner fa-spin"></i>&nbsp;</span><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"continue"),$_smarty_tpl ) );?>

                </a>
            </div>
        </div>
    </div>
    <div class="product-recommendation clonable w-hidden hidden">
        <div class="header">
            <div class="cta">
                <div class="price">
                    <span class="w-hidden hidden"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"orderfree"),$_smarty_tpl ) );?>
</span>
                    <span class="breakdown-price"></span>
                    <span class="setup-fee"><small>&nbsp;<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"ordersetupfee"),$_smarty_tpl ) );?>
</small></span>
                </div>
                <button type="button" class="btn btn-sm btn-add">
                    <span class="text"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"addtocart"),$_smarty_tpl ) );?>
</span>
                    <span class="arrow"><i class="fas fa-chevron-right"></i></span>
                </button>
            </div>
            <div class="expander">
                <i class="fas fa-chevron-right rotate" data-toggle="tooltip" data-placement="right" title="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"recommendations.learnMore"),$_smarty_tpl ) );?>
"></i>
            </div>
            <div class="content">
                <div class="headline truncate"></div>
                <div class="tagline truncate">
                    <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"recommendations.taglinePlaceholder"),$_smarty_tpl ) );?>

                </div>
            </div>
        </div>
        <div class="body clearfix"><p></p></div>
    </div>
</div>
<?php }
}
