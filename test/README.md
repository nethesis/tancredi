## Test Suite Overview

The `test/bats` directory contains end-to-end checks for the Tancredi client
implemented with [Bats](https://github.com/bats-core/bats-core). Each `.bats`
file drives the CLI through realistic provisioning scenarios by invoking
`test/bats/tancredi_client.bash`, which wraps the binary with common setup and
assertion helpers. The GitHub Actions workflow `./.github/workflows/bats.yml`
runs the same Bats suite on every push, so a green workflow means all scenarios
passed in a clean Ubuntu environment.

To run the suite locally you only need Bats installed and the usual project
dependencies (see the main repository README). Then execute:

```
bats test/bats
```

## Available Bats Scenarios

- `30_defaults.bats` — verifies the client boots with default configuration and
  produces the expected baseline output.
- `40_files_upload.bats` — exercises the file-upload flow, ensuring generated
  archives and their metadata are stored in the expected locations.
- `50_template_rendering.bats` — renders provisioning templates for Yealink T46
  phones and checks the resulting configuration against the golden fixture
  `test/fixtures/yealink-t46-expected.cfg`. When this scenario runs in CI the
  freshly rendered provisioning file is also uploaded to the GitHub Actions
  artifacts, so you can download it even if the test fails locally.

