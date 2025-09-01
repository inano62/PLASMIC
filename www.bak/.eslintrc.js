module.exports = {
  extends: [
    'plugin:react/recommended',
    'airbnb',
    'plugin:@typescript-eslint/recommended',
  ],
  rules: {
    'jsx-a11y/anchor-is-valid': 0,
    'jsx-a11y/label-has-for': 'off',
    'jsx-a11y/label-has-associated-control': 'off',
    'import/extensions': 'off',
    'import/no-unresolved': 'off',
    'no-alert': 'off',
    'no-nested-ternary': 'off',
    'react/jsx-filename-extension': [2, { extensions: ['.tsx'] }],
    'react/prop-types': 0,
  },
};
