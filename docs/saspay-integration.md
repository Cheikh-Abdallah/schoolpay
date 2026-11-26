# Intégration SasPay

## Configuration

Dans `.env`, configurez `SASPAY_ENV=sandbox` et une clé `SASPAY_API_KEY=sk_test_...` pour les essais. Passez à `SASPAY_ENV=live` avec une clé `sk_live_...` uniquement après validation des tests. La clé reste côté serveur et ne doit jamais être ajoutée au navigateur.

La clé SasPay doit avoir le scope `PAYIN` ou `BOTH`. Configurez les frais par défaut du marchand en `ADD_ON` (frais payés en supplément par le parent). SchoolPay envoie aussi `fee_charge_mode: ADD_ON`, mais SasPay ne l’applique que si l’option d’override API est activée dans votre compte. SchoolPay ne comptabilise le paiement que si SasPay confirme que le montant demandé et le montant net reçu correspondent au solde de la facture.

En production, `APP_URL` doit être l’URL publique HTTPS de SchoolPay afin que SasPay puisse rediriger le parent vers le retour de paiement.

## Base de données

Appliquez le script [2026_11_26_001_create_saspay_invoice_checkouts.sql](../database/migrations/2026_11_26_001_create_saspay_invoice_checkouts.sql) une fois à la base SchoolPay avant d’activer l’intégration.

## Parcours

Depuis la fiche d’un élève, créez un lien SasPay sur une facture ouverte puis transmettez-le au parent. Le retour navigateur ne suffit pas à confirmer un paiement : SchoolPay interroge le statut de la session puis vérifie la transaction auprès de SasPay avant de créer le paiement, son allocation à la facture et le reçu. L’équipe peut aussi relancer cette vérification depuis la page du lien de paiement.

La documentation SasPay consultée ne fournit pas de référence de session de checkout dans l’événement webhook `transaction.success`; l’intégration n’utilise donc pas cet événement pour affecter des paiements aux factures. Si le retour du parent n’est pas reçu, un utilisateur de l’établissement peut vérifier la transaction depuis l’écran du lien de paiement.
