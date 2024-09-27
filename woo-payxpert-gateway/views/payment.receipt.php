<div id="payment-container"></div>

<script async="true" src="https://connect2.payxpert.com/payment/<?php echo $customerToken; ?>/connect2pay-seamless-v1.5.0.js" data-mount-in="#payment-container" 
integrity="sha384-0IS2bunsGTTsco/UMCa56QRukMlq2pEcjUPMejy6WspCmLpGmsD3z0CmF5LQHF5X" crossorigin="anonymous"></script>




<?php /*
<iframe 
    id="payxpert_for_woocommerce_iframe" 
    src="<?php echo $payxpert_customer_url; ?>" 
    width="100%" 
    height="700" 
    scrolling="no" 
    frameborder="0" 
    border="0" 
    allowtransparency="true"
    >
</iframe>