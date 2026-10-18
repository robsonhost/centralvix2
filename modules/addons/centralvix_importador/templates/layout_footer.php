<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
    </div><!-- .cvix-content -->

    <div class="cvix-footer">
        <span>CentralVix Importador de Clientes v1.0.14</span>
        <span class="cvix-footer-sep">|</span>
        <span>Hospedagem &bull; Cloud &bull; Tecnologia</span>
    </div>
</div><!-- .cvix-module -->

<!-- Modal de confirmação -->
<div class="cvix-modal-overlay" id="cvixConfirmModal" style="display:none;">
    <div class="cvix-modal">
        <div class="cvix-modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmar importação?</h3>
        </div>
        <div class="cvix-modal-body">
            <p>Foram encontrados <strong id="cvixConfirmTotal">0</strong> clientes.</p>
            <p>Esta operação poderá criar ou atualizar registros no WHMCS.</p>
            <div class="cvix-alert cvix-alert-warning">
                <i class="fas fa-shield-alt"></i>
                Recomendamos realizar backup antes de continuar.
            </div>
        </div>
        <div class="cvix-modal-footer">
            <button type="button" class="cvix-btn cvix-btn-secondary" id="cvixConfirmCancel">Cancelar</button>
            <button type="button" class="cvix-btn cvix-btn-primary" id="cvixConfirmStart">
                <i class="fas fa-play"></i> Iniciar importação
            </button>
        </div>
    </div>
</div>

<!-- Modal SweetAlert-like -->
<div class="cvix-modal-overlay" id="cvixAlertModal" style="display:none;">
    <div class="cvix-modal cvix-modal-sm">
        <div class="cvix-modal-body text-center" id="cvixAlertBody"></div>
        <div class="cvix-modal-footer justify-center">
            <button type="button" class="cvix-btn cvix-btn-primary" id="cvixAlertOk">OK</button>
        </div>
    </div>
</div>
