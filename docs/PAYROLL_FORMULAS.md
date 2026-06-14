# SmartRH Maroc - Formules de paie

Les parametres de paie sont configurables en base de donnees. Les regles de paie doivent etre verifiees par un expert-comptable marocain avant utilisation en production.

## Salaire brut

Salaire brut =
Salaire de base
+ primes
+ indemnites imposables
+ heures supplementaires
- absences

## CNSS

Base CNSS = min(Salaire brut, plafond CNSS)

CNSS salarie = Base CNSS x taux CNSS salarie

## AMO

AMO salarie = Salaire brut x taux AMO salarie

## Salaire net avant IR

Salaire net avant IR =
Salaire brut - CNSS salarie - AMO salarie

## Frais professionnels

Frais professionnels =
Salaire net avant IR x taux frais professionnels
avec plafond configurable si applicable

Important: Frais professionnels is not a real deduction from employee net pay. It is fiscal information used to calculate IR.

## Revenu net imposable

Revenu net imposable =
Salaire net avant IR - frais professionnels

## IR

IR brut = Revenu net imposable x taux IR - deduction

IR net = max(IR brut - deductions familiales configurees, 0). Les deductions familiales ne sont pas automatisees tant qu'elles ne sont pas validees par un expert-comptable.

## Net a payer

Net a payer =
Salaire brut
- CNSS salarie
- AMO salarie
- IR net
- avances
- retenues
+ indemnites non imposables

Les frais professionnels ne reduisent jamais directement le net a payer.

## Prime d’ancienneté

Le nombre d'annees completes est calcule entre la date d'embauche et la fin de periode. Si une tranche active existe:

Prime d’ancienneté = Salaire de base x taux configuré

Regles demo:
- 2 a moins de 5 ans: 5 %
- 5 a moins de 12 ans: 10 %
- 12 a moins de 20 ans: 15 %
- 20 a moins de 25 ans: 20 %
- 25 ans et plus: 25 %

## Cumuls annuels

Les cumuls annuels additionnent, pour le meme salarie et la meme annee civile jusqu'a la periode courante:
- brut
- revenu imposable
- IR
- CNSS
- AMO
- net a payer
- deductions reelles
