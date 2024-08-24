<div class="card-inner">
    <h4>
        VouCash
    </h4>
    <p class="card-heading"></p>
    <a href="" class="btn btn-flat waves-attach" target="_blan" id="voucashBtn">
        <img src="/images/usdt.png" height="50px"/>
    </a>
</div>

<script>
    +function () {
        $("#voucashBtn").click(function () {
            if (!this.attr("href"))
                return false;
            return true;
        })
        fetch("/user/payment/purchase/voucash", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                price: {$invoice->price},
                invoice_id: {$invoice->id},
            }),
        })
        .then((response) => response.json())
        .then((response) => {
            if (response.ret == 1) {
                $("#voucashBtn").attr("href", "http://localhost:9876/payment/?amount={$invoice->price}&currency=CNY&order_id=" + response.order_id + "&notify_url=" + window.location.origin + "/payment/notify/voucash")
            }
        });
    }();

</script>