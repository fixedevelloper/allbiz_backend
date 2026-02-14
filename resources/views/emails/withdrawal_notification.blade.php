<!DOCTYPE html>
<html>
<head>
    <title>Nouvelle demande de retrait</title>
</head>
<body>
<h2>Nouvelle transaction de retrait</h2>
<p><strong>Référence :</strong> {{ $transaction->reference }}</p>
<p><strong>Utilisateur :</strong> {{ $transaction->user->phone }} (ID: {{ $transaction->user_id }})</p>
<p><strong>Montant :</strong> {{ $transaction->amount }}</p>
<p><strong>Type :</strong> {{ $transaction->type }}</p>
<p><strong>Status :</strong> {{ $transaction->status }}</p>
<p><strong>Détails :</strong></p>
<ul>
    <li>Compte : {{ $transaction->meta['account_id'] ?? '' }}</li>
    <li>Opérateur : {{ $transaction->meta['operator'] ?? '' }}</li>
    <li>Téléphone : {{ $transaction->meta['phone'] ?? '' }}</li>
    <li>Taxe : {{ $transaction->meta['tax'] ?? '' }}</li>
    <li>Montant net : {{ $transaction->meta['net_amount'] ?? '' }}</li>
</ul>
</body>
</html>
