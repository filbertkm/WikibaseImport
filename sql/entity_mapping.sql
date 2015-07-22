CREATE TABLE /*_*/wbs_entity_mapping (

  -- Local entity id
  wbs_local_id      VARBINARY(255) NOT NULL PRIMARY KEY,

  -- Foreign entity id
  wbs_original_id	VARBINARY(255) NOT NULL

) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wbs_original_idx ON /*_*/wbs_entity_mapping(wbs_original_id);
