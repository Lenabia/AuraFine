créer un repository et prendre le num de code

1. clônage : git clone https://github.com/Lenabia/projetIntranet.git
2. mettre le user et mdp token
   3)ensuite cd projetIntranet afin d'y ajouter ton fichier
   4)une fois les fichiers ajoutés manuellement faire un git add + nom du fichier ou git add \* pour tout prendre ça les ajoute à une liste temporaire en attente d'envoie
3. une fois les fichiers ajoutés à la liste d'attente d'envoie il faut impacter les modifs dans un commit qui va prendre les changement dans une version git commit -m"premier dépot"
   6° cette version de ce commit va pouvoir etre envoyer en ligne via un git push qui va demander le username et le mdp.

Si le lendemain tu as des modifs à faire des fichiers à ajouter
-git add -A pour ajouter les nouveaux fichiers
-git commit -m "dire ce qui a changé "
-git push envoie sur le dépot en ligne

3eme jour si tu dois bosser avec quelqu'un
pour partager le fichier git clone
une fois que j'aurai fini de booser je fais un git push
et mon collègue peut faire un git pull pour récupérer les dernières modifs et refaire un git push;
