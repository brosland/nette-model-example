Popis príkladu
==============

FundEntity
----------
- fond, do ktorého investori môžu investovať peniaze
- fond sa uzatvára na určitú dobu, počas ktorej správca vytvára splátky
- má 4 stavy:
	- OPEN:
		- počiatočný stav
		- investori môžu vkladať alebo vyberať svoje investície (addFunds, removeFunds)
	- CLOSED:
		- fond je uzatvorený určitý čas, investori nemôžu meniť svoje investície
		- správca fondu môže priebežbe vytvárať splátky, ktoré sa prelozdeľujú medzi investorov (payPayment)
	- FINISHED:
		- fond je definitívne ukončený a nie je možné manipulovať ani s investíciami ani vytvárať splátky
	- CANCELLED:
		- v prípade, že fond ešte nebol uzatvorený, je možné ho zrušiť.

FundService
-----------
- služba poskytujúca vytvorenie a aktualizovanie fondu

FundFacade
----------
- fasáda pre správcu fondu
- umožňuje mu:
	- vytvoriť / upravovať / uzatvoriť / ukončiť / zrušiť fond
	- pridávať splátky

FundInvestorFacade
------------------
- umožňuje investori vkladať / vyberať svoje investície
