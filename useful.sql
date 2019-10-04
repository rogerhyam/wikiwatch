
# list counts for accepted species
SELECT `exists`, count(*) as n 
FROM wikipedia as w join binomials as b on w.binomial_id = b.id
where b.wfo_accepted = 1
group by `exists`


# proportion wikipedia
SELECT  count(*) as total_accepted, 

(
select count(*) from wikipedia as w join binomials as b on w.binomial_id = b.id
where b.wfo_accepted = 1
and w.`exists` = 1
) as in_wiki,

(
select count(*) from wikipedia as w join binomials as b on w.binomial_id = b.id
where b.wfo_accepted = 1
and w.`exists` = 1
) /  count(*)
as proportion

FROM wikipedia as w join binomials as b on w.binomial_id = b.id
where b.wfo_accepted = 1


# proportion wikispecies
SELECT  count(*) as total_accepted, 

(
select count(*) from wikispecies as w join binomials as b on w.binomial_id = b.id
where b.wfo_accepted = 1
and w.`exists` = 1
) as in_wiki,

(
select count(*) from wikispecies as w join binomials as b on w.binomial_id = b.id
where b.wfo_accepted = 1
and w.`exists` = 1
) /  count(*)
as proportion

FROM wikispecies as w join binomials as b on w.binomial_id = b.id
where b.wfo_accepted = 1