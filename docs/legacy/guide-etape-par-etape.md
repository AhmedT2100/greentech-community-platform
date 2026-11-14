# 🚀 GUIDE ÉTAPE PAR ÉTAPE - Configuration Gmail

## ✨ VOUS ÊTES ICI !

Je vais vous guider **pas à pas** pour configurer l'envoi d'emails. C'est **très simple** ! 😊

---

## 📋 **ÉTAPES À SUIVRE**

### **ÉTAPE 1 : Ouvrir l'assistant de configuration** ✅

Ouvrez votre navigateur et allez sur :

```
http://127.0.0.1:8080/configure_gmail.php
```

Vous verrez une belle interface avec 3 étapes à suivre.

---

### **ÉTAPE 2 : Créer un mot de passe d'application Gmail** 🔐

#### 2.1 - Ouvrir la page de sécurité Google

Cliquez sur le bouton bleu **"Ouvrir la page de sécurité Google"** dans l'interface

OU allez directement sur :
```
https://myaccount.google.com/security
```

#### 2.2 - Activer la validation en deux étapes

1. Sur la page de sécurité, cherchez **"Validation en deux étapes"**
2. Si ce n'est pas activé, cliquez dessus et suivez les instructions
3. Vous devrez peut-être entrer votre mot de passe et votre numéro de téléphone

#### 2.3 - Créer un mot de passe d'application

1. Retournez sur https://myaccount.google.com/security
2. Cherchez **"Mots de passe des applications"** (en bas de la page)
3. Cliquez dessus
4. Dans le menu déroulant, sélectionnez **"Mail"**
5. Cliquez sur **"Générer"**
6. **COPIEZ** le code de 16 caractères qui apparaît (format : `xxxx xxxx xxxx xxxx`)

⚠️ **IMPORTANT** : Gardez ce code, vous en aurez besoin à l'étape suivante !

---

### **ÉTAPE 3 : Entrer vos informations dans l'interface** 📝

Retournez sur :
```
http://127.0.0.1:8080/configure_gmail.php
```

Remplissez le formulaire :

1. **Votre adresse Gmail** : Entrez votre email Gmail (ex: `your-account@example.com`)
2. **Mot de passe d'application** : Collez le code de 16 caractères copié à l'étape 2
3. **Nom de l'expéditeur** : Laissez "GreenTechHub" ou changez si vous voulez

Cliquez sur **"Sauvegarder la configuration"**

✅ Vous devriez voir un message de succès !

---

### **ÉTAPE 4 : Tester l'envoi d'email** 📧

Dans la même page, en bas :

1. Entrez votre email dans le champ **"Email de test"**
2. Cliquez sur **"Envoyer un email de test"**
3. Attendez quelques secondes...
4. **Vérifiez votre boîte de réception Gmail !**

📬 Vous devriez recevoir un email de bienvenue avec un beau design !

⚠️ Si vous ne le voyez pas, vérifiez vos **SPAMS** !

---

## ✅ **C'EST TERMINÉ !**

Si vous avez reçu l'email de test, **BRAVO** ! 🎉

Votre système d'envoi d'emails est maintenant **100% fonctionnel** !

---

## 🎯 **QUE SE PASSE-T-IL MAINTENANT ?**

Votre système enverra **automatiquement** des emails pour :

1. **Inscription** → Email de bienvenue 🌿
2. **Connexion avec 2FA** → Code à 6 chiffres 🔐
3. **Mot de passe oublié** → Lien de réinitialisation 🔑

---

## 🐛 **EN CAS DE PROBLÈME**

### L'email de test n'est pas envoyé

1. **Vérifiez le mot de passe d'application** :
   - Il doit faire exactement 16 caractères
   - Pas d'espaces supplémentaires
   - Copiez-collez directement depuis Google

2. **Vérifiez votre email Gmail** :
   - Assurez-vous que c'est bien un compte Gmail (@gmail.com)
   - Pas de fautes de frappe

3. **Vérifiez la validation en deux étapes** :
   - Elle DOIT être activée sur votre compte Google

### L'email arrive dans les spams

C'est normal au début ! Pour éviter ça :
- Marquez l'email comme "Non spam"
- Ajoutez l'expéditeur à vos contacts

---

## 📞 **BESOIN D'AIDE ?**

Si vous êtes bloqué à une étape :

1. Vérifiez que XAMPP est démarré (Apache + MySQL)
2. Vérifiez que vous êtes bien sur `http://127.0.0.1:8080/configure_gmail.php`
3. Ouvrez la console du navigateur (F12) pour voir les erreurs

---

## 🎉 **RÉSUMÉ DES ÉTAPES**

1. ✅ Ouvrir `configure_gmail.php`
2. ✅ Créer un mot de passe d'application Gmail
3. ✅ Entrer les informations dans le formulaire
4. ✅ Tester l'envoi d'email
5. ✅ Vérifier la réception

**C'est tout ! Simple et rapide ! 🚀**

---

## 📧 **EXEMPLE D'EMAIL REÇU**

Vous recevrez un email qui ressemble à ça :

```
De: GreenTechHub <your-account@example.com>
Objet: 🌿 Bienvenue sur GreenTechHub !

[Design avec gradient violet]

Bonjour Test Utilisateur,

Nous sommes ravis de vous accueillir dans notre communauté...

[Bouton "Accéder à la plateforme"]

© 2024 GreenTechHub
```

---

**Allez-y, suivez les étapes ! Vous allez y arriver ! 💪**
