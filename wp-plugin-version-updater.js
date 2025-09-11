module.exports.readVersion = function (contents) {
    const capturingRegex = /Version:\s*(?<vnum>[0-9]+\.[0-9]+\.[0-9]+)/;
    const found = contents.match(capturingRegex);
    if (!found) return null;
    return found.groups.vnum;
};

module.exports.writeVersion = function (_contents, version) {
    const regex = /Version:\s*[0-9]+\.[0-9]+\.[0-9]+/;
    return _contents.replace(regex, "Version:           " + version);
};
