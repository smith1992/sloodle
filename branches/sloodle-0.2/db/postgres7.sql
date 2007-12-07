CREATE TABLE prefix_sloodle (
  id SERIAL PRIMARY KEY,
  course integer NOT NULL default '0',
  name varchar(255) NOT NULL default 'untitled'
);

create table prefix_sloodle_users ( 
    id SERIAL PRIMARY KEY,
    userid integer not null,
    uuid varchar(255) not null default '',
    avname varchar(255) not null default '',
    loginposition varchar(255) not null default '',
    loginpositionexpires varchar(255) not null default '',
    loginpositionregion varchar(255) not null default '',
    loginsecuritytoken varchar(255) not null default ''
);

create table prefix_sloodle_users ( 
    id SERIAL PRIMARY KEY,
    userid integer not null,
    uuid varchar(255) not null default '',
    avname varchar(255) not null default '',
    loginposition varchar(255) not null default '',
    loginpositionexpires varchar(255) not null default '',
    loginpositionregion varchar(255) not null default '',
    loginsecuritytoken varchar(255) not null default '',
    online integer not null default '0'
);

CREATE TABLE prefix_sloodle_active_object (
    id serial NOT NULL,
    sloodle_classroom_setup_profile_id integer,
    uuid character varying(255) DEFAULT ''::character varying NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    master_uuid character varying(255) DEFAULT ''::character varying NOT NULL,
    authenticated_by_userid character varying(255) DEFAULT ''::character varying NOT NULL,
    pwd character varying(255) DEFAULT ''::character varying NOT NULL
);

ALTER TABLE ONLY prefix_sloodle_active_object ADD CONSTRAINT prefix_sloodle_active_object_pkey PRIMARY KEY (id);

CREATE TABLE prefix_sloodle_classroom_setup_profile (
    id serial NOT NULL,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    courseid integer
);

ALTER TABLE ONLY prefix_sloodle_classroom_setup_profile ADD CONSTRAINT prefix_sloodle_classroom_setup_profile_pkey PRIMARY KEY (id);

CREATE TABLE prefix_sloodle_classroom_setup_profile_entry (
    id serial NOT NULL,
    sloodle_classroom_setup_profile_id integer,
    name character varying(255) DEFAULT ''::character varying NOT NULL,
    uuid character varying(255) DEFAULT ''::character varying NOT NULL,
    relative_position character varying(255) DEFAULT ''::character varying NOT NULL
);

ALTER TABLE ONLY prefix_sloodle_classroom_setup_profile_entry ADD CONSTRAINT prefix_sloodle_classroom_setup_profile_entry_pkey PRIMARY KEY (id);
