<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-module">
    <div class="cvix-header">
        <div class="cvix-header-inner">
            <div class="cvix-brand">
                <div class="cvix-logo">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="10" fill="#0B3D91"/>
                        <path d="M10 28L20 12L30 28H10Z" fill="#00B4D8" opacity="0.9"/>
                        <circle cx="20" cy="22" r="3" fill="#FFFFFF"/>
                    </svg>
                </div>
                <div class="cvix-brand-text">
                    <span class="cvix-brand-name">CentralVix</span>
                    <h1 class="cvix-title"><?php echo CentralVixAdminUi::e($title); ?></h1>
                    <?php if ($subtitle !== ''): ?>
                    <p class="cvix-subtitle"><?php echo CentralVixAdminUi::e($subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <nav class="cvix-nav">
        <?php foreach ($nav as $item):
            if (!empty($item['href'])) {
                $href = (string) $item['href'];
            } else {
                $href = $modulelink . '&tab=' . ($item['tab'] ?? '');
                if (!empty($item['query'])) {
                    $href .= '&' . ltrim((string) $item['query'], '&');
                }
            }
            $active = CentralVixAdminUi::isNavActive($tab, $item);
            $class = 'cvix-nav-item' . ($active ? ' active' : '');
            if (!empty($item['class'])) {
                $class .= ' ' . $item['class'];
            }
            $target = !empty($item['target']) ? ' target="' . CentralVixAdminUi::e($item['target']) . '"' : '';
            $rel = !empty($item['target']) ? ' rel="noopener noreferrer"' : '';
        ?>
        <a href="<?php echo CentralVixAdminUi::e($href); ?>" class="<?php echo CentralVixAdminUi::e($class); ?>"<?php echo $target . $rel; ?>>
            <?php if (!empty($item['icon'])): ?><i class="fas <?php echo CentralVixAdminUi::e($item['icon']); ?>"></i><?php endif; ?>
            <?php echo CentralVixAdminUi::e($item['label'] ?? ''); ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($extraHtml !== ''): echo $extraHtml; endif; ?>

    <div class="cvix-content">
        <?php if (!empty($success)): ?>
        <div class="cvix-alert cvix-alert-success"><i class="fas fa-check-circle"></i> <?php echo CentralVixAdminUi::e($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
        <div class="cvix-alert cvix-alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo CentralVixAdminUi::e($error); ?></div>
        <?php endif; ?>
