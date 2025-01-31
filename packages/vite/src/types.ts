import type { AutoImportOptions } from './integrations/auto-imports'
import type { CustomIconOptions, IconsOptions } from './integrations/icons'
import type { Runner } from './integrations/run'
import type { VueOptions } from './integrations/vue'
import type { CustomComponentsOptions, CustomResolvers } from './integrations/vue-components'

export interface ViteOptions {
	/** Path to the PHP executable. */
	php?: string
	/** Options for the layout plugin. */
	layout?: LayoutOptions
	/** Options for `@vitejs/plugin-vue`. */
	vue?: false | VueOptions
	/** Options for `vite-plugin-run`. Set to `false` to disable. */
	run?: false | Runner[]
	/** Options for `unplugin-auto-import`. Set to `false` to disable. */
	autoImports?: false | AutoImportOptions
	/** Options for `unplugin-vue-components`. Set to `false` to disable. */
	vueComponents?: false | CustomComponentsOptions
	/** Options for `unplugin-icons`. Set to `false` to disable. */
	icons?: false | IconsOptions
	/** Options for custom icon collections. */
	customIcons?: CustomIconOptions
	/** Override vue component resolvers. */
	overrideResolvers?: CustomResolvers
	/** Whether to write shims. */
	shims?: boolean
	/** Enables or disable the kill-switch. */
	killSwitch?: boolean
	/** Extra `tsconfig.json` options. */
	tsconfig?: TsConfigOptions
}

export interface LayoutOptions {
	/** Custom RegExp for parsing the template string. */
	templateRegExp?: RegExp
	/** Name of the layout used when no argument is provided to `layout`. */
	defaultLayoutName?: string
}

export interface TsConfigOptions {
	/** Defines types to add to `tsconfig.json`. */
	types?: string[]
	/** Defines paths to include with `tsconfig.json`. */
	include?: string[]
	/** Defines paths to exclude with `tsconfig.json`. */
	exclude?: string[]
}
