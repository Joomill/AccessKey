# Joomla Extensions Language Translation Guidelines

## Introduction

This document provides a comprehensive guideline for professional multilingual localization of Joomla extensions. The goal is to maintain translations consistent, clear, and easy to maintain, following Joomla 5 language standards. Proper translation and file structure ensure smooth extension deployment and a quality user experience.

---

## File Structure and Supported Languages

- Language files must follow Joomla standards for components, modules, plugins, templates, and packages, all encoded in UTF-8 without BOM.
- Each extension must include English (`en-GB`) as the base language and at least five other languages: German, French, Spanish, Italian, and Dutch.
- English is the reference language; other languages should closely follow it except Spanish, which is manually translated.

---

## Naming and Terminology

- Use uppercase for language keys with specific prefixes per extension type.
- Keys should be descriptive and free of abbreviations.
- Extension and brand names are never translated.
- Technical terms usually remain in English unless clarity improves with translation.
- Terminology must be consistent across all files and languages.

---

## Formatting and Documentation Rules

- Use placeholders consistently (`%s` for text, `%d` for numbers) without mixing.
- End all help text and description lines with a period.
- Avoid extra spaces or spaces before punctuation.
- Start files with a header comment block:

```
; Example component
; Author: Jeroen Moolenschot | Joomill
; License: GNU General Public License version 3 or later
; Note: en-GB is the reference language
```

- Organize files with logical section comments like

```
; --- Toolbar ---
; --- Messages ---
; --- Configurations ---
```
---

## Translation Workflow

### Pre-Translation Checks

- Verify full file system capabilities: directory creation, file reading, writing, editing, and listing.
- Confirm availability and connectivity of translation services, prioritizing DeepL.
- Use only real file operations; simulations or assumed files are forbidden.
- Stop immediately if any capabilities are missing until resolved.


### Preparation

- Analyze extension manifest and current language files.
- Determine extension type and terminology needs.
- Prepare directories for all target languages.


### Translation Execution

- Use DeepL for professional, batch-consistent translations with appropriate tone.
- Maintain exact language constants, placeholders, and HTML entities.
- Handle technical terminology precisely.
- Split translations into logical chunks to avoid limits.


### Integration and Verification

- Use real tools to create directories and transcribed files.
- Remove XML manifests with all language entries, only add a language folder to the files/folder lists.
- Verify file existence, correct UTF-8 encoding, and file sizes.
- Spot check translations for quality, consistency, and cultural appropriateness.
- Confirm proper RTL handling where applicable.

---

## Best Practices for Translation Quality

- Preserve Joomla constants and placeholders exactly.
- Use professional and consistent commercial terminology.
- Adapt content culturally for local markets.
- Apply grammar, punctuation, and formatting rules strictly.
- Address regional language variations responsibly.

---

## File and Directory Structure Example

```
languages/
├── en-GB/
│   ├── [plugin_name].ini
│   └── [plugin_name].sys.ini
├── es-ES/
├── it-IT/
├── nl-NL/
├── fr-FR/
├── de-DE/
```


---

## Final Verification and Maintenance

- Ensure all language files are complete, accurate, and deploy without errors.
- Maintain changelog and version control for any key additions or updates.
- Regularly review and improve translations for consistency and quality.

---

## Practical Recommendations

- Begin with an outline and structure before translating.
- Work in logical, manageable sections.
- Use clear section headings and comments.
- Allow time for multiple revision rounds for accuracy and fluency.

---

This guideline ensures a streamlined, professional approach to Joomla extension multilingual localization, optimized for DeepL machine translation and real file system integration.

```