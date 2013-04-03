Here I will describe data structures and classes.

Processor/Tag/Tag saveTags():

Mongo - Document/Image:
file
title
slug
tags (final)

Redis:
temp_tags_[image_id]:
  reddit: [tag_id1 => 4, tag_id2 => 3, tag_id3 => 1],
  twitter: [tag_id2 => 2],
  source: [tag_id1 => 1]


temp_tags_score

Images:
1 => temp_tags++++ =>
2
3
4
5


