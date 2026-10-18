<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-import-wizard" id="cvixImportWizard" data-token="<?php echo htmlspecialchars($csrfToken); ?>">

    <?php if (empty($storageWritable)): ?>
    <div class="cvix-alert cvix-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        A pasta de armazenamento não está gravável. Ajuste permissões via SSH/FTP:
        <code>chmod -R 775 <?php echo htmlspecialchars($storagePath); ?></code>
    </div>
    <?php endif; ?>

    <!-- Stepper -->
    <div class="cvix-stepper">
        <div class="cvix-step active" data-step="1">
            <span class="cvix-step-num">1</span>
            <span class="cvix-step-label">Upload</span>
        </div>
        <div class="cvix-step-line"></div>
        <div class="cvix-step" data-step="2">
            <span class="cvix-step-num">2</span>
            <span class="cvix-step-label">Pré-visualização</span>
        </div>
        <div class="cvix-step-line"></div>
        <div class="cvix-step" data-step="3">
            <span class="cvix-step-num">3</span>
            <span class="cvix-step-label">Mapeamento</span>
        </div>
        <div class="cvix-step-line"></div>
        <div class="cvix-step" data-step="4">
            <span class="cvix-step-num">4</span>
            <span class="cvix-step-label">Configurações</span>
        </div>
        <div class="cvix-step-line"></div>
        <div class="cvix-step" data-step="5">
            <span class="cvix-step-num">5</span>
            <span class="cvix-step-label">Importação</span>
        </div>
    </div>

    <!-- ETAPA 1: Upload -->
    <div class="cvix-wizard-panel active" id="cvixStep1">
        <div class="cvix-card">
            <div class="cvix-card-header">
                <h3><i class="fas fa-cloud-upload-alt"></i> Etapa 1 — Upload do arquivo</h3>
            </div>
            <div class="cvix-card-body">
                <div class="cvix-upload-zone" id="cvixUploadZone">
                    <input type="file" id="cvixFileInput" accept=".csv,.xlsx,.xls" hidden>
                    <div class="cvix-upload-icon"><i class="fas fa-file-excel"></i></div>
                    <p class="cvix-upload-title">Arraste seu arquivo aqui ou clique para selecionar</p>
                    <p class="cvix-upload-hint">Formatos: CSV, XLSX, XLS — Tamanho máximo configurável nas configurações do módulo</p>
                    <div class="cvix-file-selected" id="cvixFileSelected" style="display:none;">
                        <i class="fas fa-file"></i>
                        <span id="cvixFileName"></span>
                    </div>
                </div>
                <div class="cvix-actions">
                    <button type="button" class="cvix-btn cvix-btn-primary" id="cvixAnalyzeBtn" disabled>
                        <i class="fas fa-search"></i> Analisar arquivo
                    </button>
                </div>
                <div id="cvixUploadProgress" class="cvix-progress-wrap" style="display:none;">
                    <div class="cvix-progress-bar"><div class="cvix-progress-fill" style="width:0%"></div></div>
                    <span class="cvix-progress-text">Enviando...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ETAPA 2: Pré-visualização -->
    <div class="cvix-wizard-panel" id="cvixStep2">
        <div class="cvix-card">
            <div class="cvix-card-header">
                <h3><i class="fas fa-eye"></i> Etapa 2 — Pré-visualização</h3>
            </div>
            <div class="cvix-card-body">
                <div class="cvix-info-bar">
                    <span><i class="fas fa-file-alt"></i> Arquivo: <strong id="cvixPreviewFilename">-</strong></span>
                    <span><i class="fas fa-list-ol"></i> Registros encontrados: <strong id="cvixPreviewTotal">0</strong></span>
                </div>
                <div class="cvix-table-responsive" id="cvixPreviewTableWrap">
                    <table class="cvix-table" id="cvixPreviewTable">
                        <thead id="cvixPreviewHead"></thead>
                        <tbody id="cvixPreviewBody"></tbody>
                    </table>
                </div>
                <p class="cvix-hint"><i class="fas fa-info-circle"></i> Nenhum dado será importado nesta etapa. Apenas visualização dos primeiros 10 registros.</p>
                <div class="cvix-actions">
                    <button type="button" class="cvix-btn cvix-btn-secondary" id="cvixBackToUpload"><i class="fas fa-arrow-left"></i> Voltar</button>
                    <button type="button" class="cvix-btn cvix-btn-primary" id="cvixToMapping"><i class="fas fa-arrow-right"></i> Continuar para mapeamento</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ETAPA 3: Mapeamento -->
    <div class="cvix-wizard-panel" id="cvixStep3">
        <div class="cvix-card">
            <div class="cvix-card-header">
                <h3><i class="fas fa-random"></i> Etapa 3 — Mapeamento de campos</h3>
            </div>
            <div class="cvix-card-body">
                <p class="cvix-hint"><i class="fas fa-magic"></i> Correspondências comuns foram detectadas automaticamente. Ajuste conforme necessário.</p>
                <div class="cvix-mapping-grid" id="cvixMappingGrid"></div>
                <div class="cvix-actions">
                    <button type="button" class="cvix-btn cvix-btn-secondary" id="cvixBackToPreview"><i class="fas fa-arrow-left"></i> Voltar</button>
                    <button type="button" class="cvix-btn cvix-btn-primary" id="cvixToSettings"><i class="fas fa-arrow-right"></i> Continuar para configurações</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ETAPA 4: Configurações -->
    <div class="cvix-wizard-panel" id="cvixStep4">
        <div class="cvix-card">
            <div class="cvix-card-header">
                <h3><i class="fas fa-sliders-h"></i> Etapa 4 — Configurações da importação</h3>
            </div>
            <div class="cvix-card-body">
                <div class="cvix-settings-grid">
                    <div class="cvix-settings-section">
                        <h4>Opções de processamento</h4>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_skip_existing" checked> Ignorar clientes com e-mail já cadastrado</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_update_existing"> Atualizar clientes existentes</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_validate_email" checked> Validar endereço de e-mail</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_trim_spaces" checked> Limpar espaços extras</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_remove_invalid" checked> Remover caracteres inválidos</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_normalize_phone" checked> Normalizar telefone</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_normalize_state" checked> Converter estado para UF quando possível</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_apply_default_country" checked> Definir país padrão quando vazio</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_generate_password" checked> Gerar senha aleatória quando não informada</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_check_tax_id"> Verificar duplicidade também por CPF/CNPJ</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_fill_required_custom" checked> Preencher campos personalizados obrigatórios ausentes</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_sync_tax_custom" checked> Copiar CPF/CNPJ para campo personalizado configurado</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_allow_missing_tax" checked> Importar sem CPF/CNPJ (cadastro pendente — recomendado)</label>
                        <label class="cvix-checkbox"><input type="checkbox" id="opt_generate_missing_tax"> Tentar gerar CPF/CNPJ provisório via API (alternativa)</label>
                        <p class="cvix-hint"><i class="fas fa-info-circle"></i> Com <strong>Importar sem CPF/CNPJ</strong>, o cliente entra no WHMCS sem documento (ideal para NotificaZap). Depois você envia notificação pedindo atualização do cadastro.</p>
                    </div>
                    <div class="cvix-settings-section">
                        <h4>Valores padrão para campos ausentes</h4>
                        <p class="cvix-hint">Usados quando a planilha não informa telefone, endereço, UF ou campos personalizados exigidos pelo WHMCS.</p>
                        <div class="cvix-form-group">
                            <label>Telefone padrão</label>
                            <input type="text" id="opt_default_phone" class="cvix-form-control" value="(11) 99999-9999">
                        </div>
                        <div class="cvix-form-group">
                            <label>Endereço padrão (address1)</label>
                            <input type="text" id="opt_default_address1" class="cvix-form-control" value="Não informado">
                        </div>
                        <div class="cvix-form-group">
                            <label>Cidade padrão</label>
                            <input type="text" id="opt_default_city" class="cvix-form-control" value="Não informado">
                        </div>
                        <div class="cvix-form-group">
                            <label>UF padrão (Brasil)</label>
                            <input type="text" id="opt_default_state" class="cvix-form-control" value="SP" maxlength="2">
                        </div>
                        <div class="cvix-form-group">
                            <label>Texto padrão — campos personalizados (texto)</label>
                            <input type="text" id="opt_default_custom_text" class="cvix-form-control" value="Importação em massa">
                        </div>
                    </div>
                    <div class="cvix-settings-section">
                        <h4>Valores padrão WHMCS</h4>
                        <div class="cvix-form-group">
                            <label>Moeda padrão</label>
                            <select id="opt_default_currency" class="cvix-form-control">
                                <option value="">— Usar padrão do WHMCS —</option>
                                <?php foreach ($currencies as $cur): ?>
                                <option value="<?php echo htmlspecialchars($cur->code); ?>" <?php echo !empty($settings['default_currency']) && $settings['default_currency'] === $cur->code ? 'selected' : ($cur->default ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($cur->code); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cvix-form-group">
                            <label>Grupo de clientes</label>
                            <select id="opt_default_groupid" class="cvix-form-control">
                                <option value="">— Nenhum —</option>
                                <?php foreach ($clientGroups as $group): ?>
                                <option value="<?php echo (int) $group->id; ?>" <?php echo !empty($settings['default_groupid']) && (int)$settings['default_groupid'] === (int)$group->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group->groupname); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cvix-form-group">
                            <label>Idioma</label>
                            <select id="opt_default_language" class="cvix-form-control">
                                <?php foreach ($languages as $code => $label): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($settings['default_language'] ?? 'portuguese-br') === $code ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cvix-form-group">
                            <label>País padrão</label>
                            <select id="opt_default_country_val" class="cvix-form-control">
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars($country->countrycode); ?>" <?php echo ($settings['default_country'] ?? 'BR') === $country->countrycode ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country->country); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cvix-form-group">
                            <label>Campo personalizado para CPF/CNPJ (opcional)</label>
                            <select id="opt_tax_id_custom_field" class="cvix-form-control">
                                <option value="">— Nenhum —</option>
                                <?php foreach ($customFields as $cf): ?>
                                <option value="<?php echo (int) $cf->id; ?>" <?php echo !empty($detectedTaxCustomFieldId) && (int) $detectedTaxCustomFieldId === (int) $cf->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cf->fieldname); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="cvix-actions">
                    <button type="button" class="cvix-btn cvix-btn-secondary" id="cvixBackToMapping"><i class="fas fa-arrow-left"></i> Voltar</button>
                    <button type="button" class="cvix-btn cvix-btn-primary" id="cvixSaveAndContinue"><i class="fas fa-save"></i> Salvar e continuar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ETAPA 5: Importação -->
    <div class="cvix-wizard-panel" id="cvixStep5">
        <div class="cvix-card">
            <div class="cvix-card-header">
                <h3><i class="fas fa-cogs"></i> Etapa 5 — Importação</h3>
            </div>
            <div class="cvix-card-body">
                <div id="cvixImportReady">
                    <p>Pronto para importar <strong id="cvixImportTotal">0</strong> clientes.</p>
                    <button type="button" class="cvix-btn cvix-btn-primary cvix-btn-lg" id="cvixStartImportBtn">
                        <i class="fas fa-play"></i> Iniciar importação
                    </button>
                </div>

                <div id="cvixImportProgress" style="display:none;">
                    <h4><i class="fas fa-spinner fa-spin"></i> Importando clientes...</h4>
                    <p class="cvix-progress-count"><span id="cvixProgressCurrent">0</span> de <span id="cvixProgressTotal">0</span></p>
                    <div class="cvix-progress-bar cvix-progress-lg">
                        <div class="cvix-progress-fill" id="cvixProgressBar" style="width:0%"></div>
                    </div>
                    <span class="cvix-progress-percent" id="cvixProgressPercent">0%</span>

                    <div class="cvix-live-stats">
                        <div class="cvix-live-stat"><span class="label">Importados</span><span class="value" id="cvixLiveImported">0</span></div>
                        <div class="cvix-live-stat"><span class="label">Atualizados</span><span class="value" id="cvixLiveUpdated">0</span></div>
                        <div class="cvix-live-stat"><span class="label">Ignorados</span><span class="value" id="cvixLiveSkipped">0</span></div>
                        <div class="cvix-live-stat cvix-live-error"><span class="label">Erros</span><span class="value" id="cvixLiveErrors">0</span></div>
                    </div>

                    <div class="cvix-recent-errors" id="cvixRecentErrors"></div>
                </div>

                <div id="cvixImportComplete" style="display:none;">
                    <div class="cvix-complete-banner">
                        <i class="fas fa-check-circle"></i>
                        <h3>Importação concluída</h3>
                    </div>
                    <div class="cvix-final-stats">
                        <div class="cvix-final-stat"><span>Total analisado</span><strong id="cvixFinalTotal">0</strong></div>
                        <div class="cvix-final-stat"><span>Importados</span><strong id="cvixFinalImported">0</strong></div>
                        <div class="cvix-final-stat"><span>Atualizados</span><strong id="cvixFinalUpdated">0</strong></div>
                        <div class="cvix-final-stat"><span>Ignorados</span><strong id="cvixFinalSkipped">0</strong></div>
                        <div class="cvix-final-stat"><span>Erros</span><strong id="cvixFinalErrors">0</strong></div>
                    </div>
                    <div class="cvix-actions">
                        <a href="#" class="cvix-btn cvix-btn-primary" id="cvixViewReport"><i class="fas fa-file-alt"></i> Ver relatório completo</a>
                        <a href="#" class="cvix-btn cvix-btn-secondary" id="cvixDownloadReport"><i class="fas fa-download"></i> Baixar relatório CSV</a>
                        <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=import" class="cvix-btn cvix-btn-outline"><i class="fas fa-redo"></i> Nova importação</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var CVIX_WHMCS_FIELDS = <?php echo json_encode($whmcsFields, JSON_UNESCAPED_UNICODE); ?>;
var CVIX_CUSTOM_FIELDS = <?php echo json_encode(array_map(function($cf) {
    return ['id' => $cf->id, 'name' => $cf->fieldname];
}, $customFields), JSON_UNESCAPED_UNICODE); ?>;
</script>
