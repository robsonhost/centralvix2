{if !empty($client.companyname)}
    {$client.companyname|escape}
{elseif !empty($client.fullName)}
    {$client.fullName|escape}
{elseif !empty($clientsdetails.companyname)}
    {$clientsdetails.companyname|escape}
{elseif !empty($clientsdetails.firstname)}
    {$clientsdetails.firstname|escape} {$clientsdetails.lastname|escape}
{else}
    {lang key='clientarea'}
{/if}
