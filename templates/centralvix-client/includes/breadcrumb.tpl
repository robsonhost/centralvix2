<nav class="cv-breadcrumb-wrap" aria-label="breadcrumb">
    <ol class="cv-breadcrumb-list">
        {foreach $breadcrumb as $item}
            <li class="cv-breadcrumb-item{if $item@last} active{/if}"{if $item@last} aria-current="page"{/if}>
                {if !$item@last}
                    <a href="{$item.link}">{$item.label}</a>
                    <i class="fas fa-chevron-right cv-breadcrumb-sep" aria-hidden="true"></i>
                {else}
                    <span>{$item.label}</span>
                {/if}
            </li>
        {/foreach}
    </ol>
</nav>
