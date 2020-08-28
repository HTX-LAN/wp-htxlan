# HTX Lan - Wordpress plugin
HTX Lan Wordpress plugin for tilmelding til HTX Lan, lavet af elever fra HTX

## Funktioner
Dette plugin bygger på at, at man skal kunne lave helt specielle formulare, med de inputfelter som man gerne vil have.
Systemet bygger på at man efter tilmeldingen, kan registrer om deltagerne har betalt, hvad de har betalt med og om de er ankommet til eventet.

Dertil vil det også være muligt at sætte en økonomi side op, således at man kan holde styr på hvor mange penge man bruger og tjener, og hvor meget der egentligt er kommet ind.

Du kan se mere om pluginet [her](https://htx-lan.github.io/wp-htxlan/pages#main-plugin-side).

## Udviklere
Dette plugin er lavet af frivillige elever fra LAN udvalget på HTX i Nyk. F.
<br>Disse er på nuværende tidspunkt:
- Mikkel
- Frej

### Til Udviklere
Her er et diagram der viser hvordan de forskellige branches interagerer med hinanden. Master branchen er låst og kan kun opdateres via pull-requests fra andre branches. Under normal brug vil features blive lavet som branches der udspringer fra develop og når der så er tilpas mange commits i develop laver man en pull-request til masteren. Som så skal reviewes af mindst en anden før der merges. Derefter vil build scriptet gå igang og lave den nyeste version af pluginnet som så automatisk vil ende på hovedsitet! Så venligst tjek tingene på et lokalt test site først ;-)

![image](https://cdn.discordapp.com/attachments/618321740757467147/748956668137570385/unknown.png)

## Theme
For at få det bedste ud af denne form, er det vigtigt at bruge et ordentligt theme og layout for siden.
Størstedel af siden er designet ved brug af ["The conference"](https://da.wordpress.org/themes/the-conference/)

## Brug
### Installation
For at sætte pluginnet op på nuværende tidspunkt, skal pluginet først aktiveres, herefter skal de nødvendige databaser så oprettes, som sker ved at trykke på knappen "Opret database"
<br> *Dette skal senere hen laves om til at gøres automatisk, således at databaser slettes og oprettes som pluginnet aktiveres og slettes*

### Dagligt brug
Når pluginnet er aktiveret, vil der automatisk allerede være lavet en ny formular, med en standard opsætning. Denne standarde opsætning kommer med alle nye formulare der laves. 

Standard opsætning:
- Fornavn
- Efternavn
- E-mail
- Telefon

Ud af disse, kan E-mail ikke ændres, fordi denne bruges til at registrere brugere
