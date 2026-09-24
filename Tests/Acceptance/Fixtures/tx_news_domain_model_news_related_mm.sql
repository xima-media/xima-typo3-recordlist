-- News 1 relates to news 2, 3 and 4. `related` sets MM_opposite_field, so the record owning the field is stored in
-- uid_foreign and the related records in uid_local.
INSERT INTO `tx_news_domain_model_news_related_mm` (`uid_local`, `uid_foreign`, `sorting`, `sorting_foreign`)
VALUES
(2, 1, 0, 1),
(3, 1, 0, 2),
(4, 1, 0, 3);
