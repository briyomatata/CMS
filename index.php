<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Payment Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .payment-card { max-width: 500px; margin: 50px auto; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-mpesa { background-color: #28a745; color: white; font-weight: bold; }
        .btn-mpesa:hover { background-color: #218838; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="card payment-card p-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Church Contributions</h3>
            <p class="text-muted">Secure M-Pesa Payment</p>
        </div>

       <form action="stkpush.php" method="POST" id="multiPaymentForm">
    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label fw-bold">Select Contributions</label>
        
        <div class="input-group mb-2">
            <div class="input-group-text">
                <input class="form-check-input mt-0 item-check" type="checkbox" name="items[]" value="Tithe" data-target="amt-tithe">
                <span class="ms-2">Tithe</span>
            </div>
            <input type="number" id="amt-tithe" name="amounts[Tithe]" class="form-control item-amount" placeholder="Amount" disabled>
        </div>

        <div class="input-group mb-2">
            <div class="input-group-text">
                <input class="form-check-input mt-0 item-check" type="checkbox" name="items[]" value="Building Fund" data-target="amt-building">
                <span class="ms-2">Building Fund</span>
            </div>
            <input type="number" id="amt-building" name="amounts[Building Fund]" class="form-control item-amount" placeholder="Amount" disabled>
        </div>

        <div class="card p-2 border-dashed">
            <div class="input-group mb-2">
                <div class="input-group-text">
                    <input class="form-check-input mt-0 item-check" type="checkbox" name="items[]" value="Other" id="check-other" data-target="amt-other">
                    <span class="ms-2">Other Purpose</span>
                </div>
                <input type="text" name="other_purpose_name" id="name-other" class="form-control" placeholder="What are you paying for?" disabled>
            </div>
            <div class="input-group">
                <span class="input-group-text">Amount KES</span>
                <input type="number" id="amt-other" name="amounts[Other]" class="form-control item-amount" placeholder="0.00" disabled>
            </div>
        </div>
    </div>

    <div class="alert alert-success">
        <strong>Total to Pay: KES <span id="display-total">0</span></strong>
        <input type="hidden" name="total_amount" id="hidden-total" value="0">
    </div>

    <div class="mb-3">
        <label class="form-label">M-Pesa Number</label>
        <input type="text" name="phone" class="form-control" placeholder="2547XXXXXXXX" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Proceed to Payment</button>
</form>

<script>
document.querySelectorAll('.item-check').forEach(check => {
    check.addEventListener('change', function() {
        const targetId = this.dataset.target;
        const amtInput = document.getElementById(targetId);
        
        // Enable/Disable amount input
        amtInput.disabled = !this.checked;
        if(!this.checked) amtInput.value = '';

        // Special handling for the "Other" purpose name field
        if(this.id === 'check-other') {
            const nameInput = document.getElementById('name-other');
            nameInput.disabled = !this.checked;
            if(!this.checked) nameInput.value = '';
            nameInput.required = this.checked;
        }

        calculateTotal();
    });
});

document.querySelectorAll('.item-amount').forEach(input => {
    input.addEventListener('input', calculateTotal);
});

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-amount:not(:disabled)').forEach(input => {
        total += Number(input.value) || 0;
    });
    document.getElementById('display-total').innerText = total.toLocaleString();
    document.getElementById('hidden-total').value = total;
}
</script>
    </div>
</div>

<script>
    function toggleOtherField() {
        var category = document.getElementById("paymentCategory").value;
        var otherField = document.getElementById("otherField");
        var purposeInput = document.getElementById("purposeInput");

        if (category === "Other") {
            otherField.style.display = "block";
            purposeInput.setAttribute("required", "");
        } else {
            otherField.style.display = "none";
            purposeInput.removeAttribute("required");
        }
    }
</script>

</body>
</html>