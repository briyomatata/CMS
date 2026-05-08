<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manual Receipting - Laiser Hill SDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 p-4">
                <h4 class="fw-bold mb-4">Issue Manual Receipt</h4>
                <form action="process_manual.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="0712345678" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose/Details (Format: Name (KES Amount))</label>
                        <textarea name="purpose_details" class="form-control" rows="2" placeholder="Tithe (KES 1000), Offering (KES 500)" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Amount (KES)</label>
                        <input type="number" name="amount" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Save & Send Email Receipt</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>