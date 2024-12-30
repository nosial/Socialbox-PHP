create table encryption_records
(
    data mediumtext not null comment 'The data column',
    iv   mediumtext not null comment 'The initialization vector column',
    tag  mediumtext not null comment 'The authentication tag used to verify if the data was tampered'
)
    comment 'Table for housing encryption records for the server';

