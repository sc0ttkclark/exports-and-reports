#!/usr/bin/env python3
"""The release notes of a version, from readme.txt: its changelog section and the sections of every version
after the one given (the last published release), newest first, each with its upgrade notice when the
readme has one. A single section comes without its heading; several come each under a heading.

    bin/release-notes.py readme.txt 1.2.6 1.2.5    # the section of 1.2.6 alone
    bin/release-notes.py readme.txt 1.2.6 1.1.3    # the sections of 1.2.6 down to, not including, 1.1.3
    bin/release-notes.py readme.txt 1.2.6          # every section from 1.2.6 down: nothing was published yet
"""
import re
import sys


def sections(text, title):
    """The '= version =' blocks of one '== Title ==' section, in the readme's order: [(version, lines)]."""
    block = re.search(r'^== ' + re.escape(title) + r' ==\n(.*?)(?=^== |\Z)', text, re.S | re.M)
    if not block:
        return []
    found = []
    for match in re.finditer(r'^= (\S+) =\n(.*?)(?=^= \S+ =|\Z)', block.group(1), re.S | re.M):
        found.append((match.group(1), match.group(2).strip('\n')))
    return found


def main():
    readme, version, since = (sys.argv + ['', ''])[1:4]
    text = open(readme, encoding='utf-8').read()
    changelog = sections(text, 'Changelog')
    notices = dict(sections(text, 'Upgrade Notice'))
    versions = [v for v, _ in changelog]
    if version not in versions:
        return 1
    start = versions.index(version)
    stop = versions.index(since) if since in versions else len(versions)
    chosen = changelog[start:stop] if stop > start else changelog[start:start + 1]
    out = []
    for v, body in chosen:
        if len(chosen) > 1:
            out.append('## ' + v)
        out.append(body)
        if v in notices and notices[v].strip():
            out.append('')
            out.append('**Upgrade notice:** ' + ' '.join(notices[v].split()))
        out.append('')
    sys.stdout.write('\n'.join(out).rstrip('\n') + '\n')
    return 0


if __name__ == '__main__':
    sys.exit(main())
