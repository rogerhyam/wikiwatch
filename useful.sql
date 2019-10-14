
# circular alt_name references
select n.name_num, mv.ALT_NAME, n.Accept, n.NAME_FULL from 
names_mv as mv join names as n on n.name_num = mv.name_num
where mv.ALT_NAME = mv.NAME_NUM
# 84 rows

# Accepted names that have alternate names that are accepted
select n1.name_num,  n1.Accept, n1.NAME_FULL, n2.name_num, n2.ACCEPT, n2.NAME_FULL
from names_mv as mv
join names as n1 on n1.name_num = mv.name_num
join names as n2 on mv.ALT_NAME = n2.name_num
where n1.ACCEPT = 'A'
and n2.ACCEPT = 'A'
# 1200 +

# Synonyms of synonyms
select
 n1.name_num,  n1.Accept, n1.NAME_FULL, n2.name_num, n2.ACCEPT, n2.NAME_FULL
from names_mv as mv
join names as n1 on n1.name_num = mv.name_num
join names as n2 on mv.ALT_NAME = n2.name_num
where n1.ACCEPT in ('S','ST','SN','SS')
and n2.ACCEPT in ('S','ST','SN','SS')
# 6676 

# Synonyms with more than one accepted name
select
 n1.name_num,  n1.Accept, n1.NAME_FULL, count(*) as n_accepted_names
from names_mv as mv
join names as n1 on n1.name_num = mv.name_num
join names as n2 on mv.ALT_NAME = n2.name_num
where n1.ACCEPT in ('S','ST','SN','SS')
and n2.ACCEPT = 'A'
group by n1.name_num
having n_accepted_names > 1
order by n_accepted_names desc



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