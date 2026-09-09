<?php
// Copie ce fichier en "env.local.php" (même dossier) et renseigne tes propres valeurs.
// env.local.php n'est PAS versionné (voir .gitignore) : il ne sert qu'en local (XAMPP).
// En production (Render), ces mêmes variables sont définies dans le dashboard Render (Environment).

putenv('SMTP_HOST=smtp.gmail.com');
putenv('SMTP_PORT=587');
putenv('SMTP_USER=ton-adresse@gmail.com');
putenv('SMTP_PASS=xxxxxxxxxxxxxxxx'); // mot de passe d'application Gmail (16 caractères)
putenv('MAIL_CC=adresse-en-copie@outlook.com'); // optionnel
