var section = 'default';
if (process.env.npm_config_section) {
    var section = process.env.npm_config_section;
}

require(__dirname + '/webpack.' + section + '.mix.js');
